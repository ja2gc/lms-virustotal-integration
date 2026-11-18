<?php
/**
 * doodle - Flashcards controller (SM-2 spaced repetition + AI generation)
 *
 * @package doodle
 * @author  Death Legion Team
 */

class FlashcardController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        Auth::requireLogin();
    }

    public function index(): void
    {
        $uid = Auth::id();
        // Show user's decks (own + course decks for enrolled courses + public)
        $decks = Database::fetchAll(
            "SELECT d.*,
                    (SELECT COUNT(*) FROM flashcards WHERE deck_id = d.id) AS card_count,
                    (SELECT COUNT(*) FROM flashcard_reviews fr
                     JOIN flashcards f ON fr.flashcard_id = f.id
                     WHERE f.deck_id = d.id AND fr.user_id = :uid AND fr.next_review <= :today) AS due_count,
                    c.title AS course_title
             FROM flashcard_decks d
             LEFT JOIN courses c ON d.course_id = c.id
             WHERE d.user_id = :uid
                OR d.is_public = 1
                OR (d.course_id IN (SELECT course_id FROM enrollments WHERE student_id = :uid AND status = 'active'))
                OR :is_admin = 1
                OR (d.course_id IN (SELECT id FROM courses WHERE teacher_id = :uid))
             ORDER BY d.created_at DESC",
            ['uid' => $uid, 'today' => date('Y-m-d'), 'is_admin' => Auth::isAdmin() ? 1 : 0]
        );

        // Count due cards across all decks
        $dueCount = Database::count('flashcard_reviews fr JOIN flashcards f ON fr.flashcard_id = f.id JOIN flashcard_decks d ON f.deck_id = d.id',
            '(d.user_id = :uid OR d.is_public = 1 OR :is_admin = 1 OR d.course_id IN (SELECT id FROM courses WHERE teacher_id = :uid) OR d.course_id IN (SELECT course_id FROM enrollments WHERE student_id = :uid2 AND status = "active")) AND fr.user_id = :uid3 AND fr.next_review <= :today',
            ['uid' => $uid, 'uid2' => $uid, 'uid3' => $uid, 'today' => date('Y-m-d'), 'is_admin' => Auth::isAdmin() ? 1 : 0]);

        $this->render('flashcards/index', [
            'pageTitle' => 'Flashcards',
            'decks'     => $decks,
            'dueCount'  => $dueCount,
        ]);
    }

    public function view(int $deckId): void
    {
        $deck = $this->requireDeck($deckId);
        $cards = Database::fetchAll('SELECT * FROM flashcards WHERE deck_id = :did ORDER BY sort_order, id', ['did' => $deckId]);
        $this->render('flashcards/view', [
            'pageTitle' => $deck['title'],
            'deck'      => $deck,
            'cards'     => $cards,
        ]);
    }

    public function create(): void
    {
        $courses = Auth::isAdmin()
            ? Database::fetchAll('SELECT id, title FROM courses ORDER BY title')
            : (Auth::isStudent()
                ? Database::fetchAll("SELECT c.id, c.title FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE e.student_id = :uid AND e.status = 'active'", ['uid' => Auth::id()])
                : Database::fetchAll("SELECT id, title FROM courses WHERE teacher_id = :tid ORDER BY title", ['tid' => Auth::id()]));
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = is_string($this->input('title')) ? trim($this->input('title')) : '';
            $desc = is_string($this->input('description')) ? trim($this->input('description')) : '';
            $courseId = (int) $this->input('course_id', 0) ?: null;
            $isPublic = (int) ($this->input('is_public', '0') === '1');
            if ($courseId !== null && !in_array($courseId, array_map('intval', array_column($courses, 'id')), true)) {
                $this->json(['ok'=>false, 'error'=>'You cannot add a deck to that course.'], 403);
            }
            $cards = [];
            $fronts = (array) $this->input('front', []);
            $backs = (array) $this->input('back', []);
            $invalid = count($fronts) !== count($backs);
            foreach ($fronts as $i => $front) {
                $back = $backs[$i] ?? '';
                if (!is_string($front) || !is_string($back)) { $invalid = true; continue; }
                $front = trim($front); $back = trim($back);
                if ($front === '' && $back === '') continue;
                if ($front === '' || $back === '') { $invalid = true; continue; }
                $cards[] = ['front'=>$front, 'back'=>$back];
            }
            if ($title === '' || mb_strlen($title) > 255 || !$cards || $invalid) {
                http_response_code(422);
                $this->render('flashcards/create', ['pageTitle'=>'Create Deck', 'courses'=>$courses,
                    'old'=>['title'=>$title, 'description'=>$desc, 'course_id'=>$courseId, 'is_public'=>$isPublic,
                        'front'=>array_map(static fn($value)=>is_string($value)?$value:'', $fronts),
                        'back'=>array_map(static fn($value)=>is_string($value)?$value:'', $backs)],
                    'error'=>'Enter a title (up to 255 characters) and at least one complete question/answer pair.']);
                return;
            }
            $db = Database::getInstance();
            $db->beginTransaction();
            try {
                $deckId = Database::insert('flashcard_decks', [
                    'course_id'  => $courseId,
                    'user_id'    => Auth::id(),
                    'title'      => $title,
                    'description'=> $desc,
                    'is_public'  => $isPublic,
                    'is_ai_generated' => 0,
                ]);
                // Add cards
                foreach ($cards as $i => $card) {
                    Database::insert('flashcards', [
                        'deck_id'    => $deckId,
                        'front'      => $card['front'],
                        'back'       => $card['back'],
                        'sort_order' => $i,
                    ]);
                }
                $db->commit();
            } catch (Throwable $error) {
                $db->rollBack();
                throw $error;
            }
            $this->flash('success', 'Deck created.');
            $this->redirect('flashcards/view/' . $deckId);
        }
        $this->render('flashcards/create', [
            'pageTitle' => 'Create Deck',
            'courses'   => $courses,
        ]);
    }

    public function study(int $deckId): void
    {
        $deck = $this->requireDeck($deckId);
        // Initialize review rows for all cards in deck that user hasn't seen yet
        $cards = Database::fetchAll('SELECT * FROM flashcards WHERE deck_id = :did ORDER BY sort_order, id', ['did' => $deckId]);
        foreach ($cards as $c) {
            $exists = Database::fetch('SELECT id FROM flashcard_reviews WHERE flashcard_id = :fid AND user_id = :uid', ['fid' => $c['id'], 'uid' => Auth::id()]);
            if (!$exists) {
                Database::insert('flashcard_reviews', [
                    'flashcard_id'  => $c['id'],
                    'user_id'       => Auth::id(),
                    'ease_factor'   => 2.50,
                    'interval_days' => 0,
                    'repetitions'   => 0,
                    'next_review'   => date('Y-m-d'),
                ]);
            }
        }
        // Pick cards due today (or all if none due — first study)
        $due = Database::fetchAll(
            "SELECT f.*, fr.id AS review_id, fr.ease_factor, fr.interval_days, fr.repetitions
             FROM flashcards f
             JOIN flashcard_reviews fr ON fr.flashcard_id = f.id
             WHERE f.deck_id = :did AND fr.user_id = :uid AND fr.next_review <= :today
             ORDER BY f.sort_order",
            ['did' => $deckId, 'uid' => Auth::id(), 'today' => date('Y-m-d')]
        );
        if (empty($due)) {
            $this->flash('success', 'No cards due for review right now. Come back later!');
            $this->redirect('flashcards/view/' . $deckId);
        }
        $this->render('flashcards/study', [
            'pageTitle' => 'Studying · ' . $deck['title'],
            'deck'      => $deck,
            'cards'     => $due,
        ]);
    }

    /** SM-2 algorithm: process a review rating (1=again, 2=hard, 3=good, 4=easy). */
    public function review(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(['ok' => false], 405);
        $reviewId = (int) $this->input('review_id', 0);
        $rating = (int) $this->input('rating', 3);
        if ($rating < 1) $rating = 1;
        if ($rating > 4) $rating = 4;
        $review = Database::fetch('SELECT * FROM flashcard_reviews WHERE id = :id AND user_id = :uid', ['id' => $reviewId, 'uid' => Auth::id()]);
        if (!$review) $this->json(['ok' => false, 'error' => 'Review not found'], 404);
        $card = Database::fetch('SELECT deck_id FROM flashcards WHERE id = :id', ['id'=>$review['flashcard_id']]);
        $this->requireDeck((int) $card['deck_id']);

        $ef = (float) $review['ease_factor'];
        $reps = (int) $review['repetitions'];
        $interval = (int) $review['interval_days'];

        // SM-2 algorithm
        if ($rating < 3) {
            // Failed — reset
            $reps = 0;
            $interval = 1;
        } else {
            // Passed
            if ($reps === 0) {
                $interval = 1;
            } elseif ($reps === 1) {
                $interval = 6;
            } else {
                $interval = (int) round($interval * $ef);
            }
            $reps++;
            // Update ease factor
            $ef = $ef + (0.1 - (5 - $rating) * (0.08 + (5 - $rating) * 0.02));
            if ($ef < 1.3) $ef = 1.3;
        }
        $nextReview = date('Y-m-d', strtotime("+$interval days"));

        Database::update('flashcard_reviews', [
            'ease_factor'   => round($ef, 2),
            'interval_days' => $interval,
            'repetitions'   => $reps,
            'next_review'   => $nextReview,
            'last_reviewed' => date('Y-m-d'),
        ], 'id = :id', ['id' => $reviewId]);

        Gamification::award(Auth::id(), 'flashcard_review');
        $this->json(['ok' => true, 'next_review' => $nextReview, 'interval' => $interval]);
    }

    /** AI: auto-generate a deck from course content. */
    public function generateFromCourse(int $courseId): void
    {
        $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $courseId]);
        if (!$course) { $this->redirect('flashcards'); }
        $canAccess = Auth::isAdmin() || (Auth::isTeacher() && $course['teacher_id'] == Auth::id()) || Auth::isEnrolled($courseId);
        if (!$canAccess) { $this->flash('error', 'Access denied.'); $this->redirect('flashcards'); }
        $lessons = Database::fetchAll(
            "SELECT l.title, l.content FROM lessons l JOIN modules m ON l.module_id = m.id
             WHERE m.course_id = :cid AND l.content_type = 'text' AND l.content IS NOT NULL AND l.content != ''",
            ['cid' => $courseId]
        );
        if (empty($lessons)) { $this->flash('error', 'This course has no text lessons to generate from.'); $this->redirect('flashcards'); }
        $deckId = Database::insert('flashcard_decks', [
            'course_id'      => $courseId,
            'user_id'        => Auth::id(),
            'title'          => 'AI Flashcards · ' . $course['title'],
            'description'    => 'Auto-generated from ' . count($lessons) . ' lesson(s)',
            'is_ai_generated'=> 1,
        ]);
        $totalCards = 0;
        foreach ($lessons as $l) {
            $text = strip_tags($l['content']);
            $cards = AIEngine::generateFlashcards($text, 8);
            foreach ($cards as $i => $c) {
                Database::insert('flashcards', [
                    'deck_id'    => $deckId,
                    'front'      => $c['front'],
                    'back'       => $c['back'],
                    'sort_order' => $totalCards + $i,
                ]);
            }
            $totalCards += count($cards);
        }
        $this->flash('success', "AI generated $totalCards flashcards from course content.");
        $this->redirect('flashcards/view/' . $deckId);
    }

    public function delete(int $deckId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(['ok'=>false, 'error'=>'Use POST to delete a deck.'], 405);
        $deck = $this->requireDeck($deckId);
        if ($deck['user_id'] != Auth::id() && !Auth::isAdmin()) {
            $this->flash('error', 'You can only delete your own decks.');
            $this->redirect('flashcards');
        }
        Database::delete('flashcard_decks', 'id = :id', ['id' => $deckId]);
        $this->flash('success', 'Deck deleted.');
        $this->redirect('flashcards');
    }

    private function requireDeck(int $deckId): array
    {
        $deck = Database::fetch('SELECT * FROM flashcard_decks WHERE id = :id', ['id'=>$deckId]);
        if (!$deck) $this->json(['ok'=>false, 'error'=>'Deck not found.'], 404);
        $canRead = Auth::isAdmin() || (int) $deck['user_id'] === Auth::id() || (int) $deck['is_public'] === 1
            || ($deck['course_id'] !== null && (Auth::ownsCourse((int) $deck['course_id']) || Auth::isEnrolled((int) $deck['course_id'])));
        if (!$canRead) $this->json(['ok'=>false, 'error'=>'You cannot access this deck.'], 403);
        return $deck;
    }
}
