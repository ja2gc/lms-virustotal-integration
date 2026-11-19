"""Standard-library loopback client for submissions.php; credentials stay in memory."""
import concurrent.futures
import ctypes
import csv
import http.cookiejar
import json
import os
from pathlib import Path
import platform
import re
import statistics
import sys
import threading
import time
import urllib.error
import urllib.parse
import urllib.request


def host_resources():
    """Host-wide samples include unrelated processes; never represent app-only CPU/RAM."""
    if platform.system() != "Windows":
        return {}
    from ctypes import wintypes
    class MemoryStatus(ctypes.Structure):
        _fields_ = [("length", wintypes.DWORD), ("memoryLoad", wintypes.DWORD)] + [(name, ctypes.c_ulonglong) for name in ("totalPhysical", "availablePhysical", "totalPageFile", "availablePageFile", "totalVirtual", "availableVirtual", "availableExtended")]
    memory = MemoryStatus()
    memory.length = ctypes.sizeof(memory)
    if not ctypes.windll.kernel32.GlobalMemoryStatusEx(ctypes.byref(memory)):
        return {}
    idle, kernel, user = wintypes.FILETIME(), wintypes.FILETIME(), wintypes.FILETIME()
    if not ctypes.windll.kernel32.GetSystemTimes(ctypes.byref(idle), ctypes.byref(kernel), ctypes.byref(user)):
        return {}
    ticks = lambda value: (value.dwHighDateTime << 32) | value.dwLowDateTime
    return {"hostMemoryTotalBytes": memory.totalPhysical, "hostMemoryAvailableBytes": memory.availablePhysical, "idleTicks": ticks(idle), "totalTicks": ticks(kernel) + ticks(user)}


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, request, file_pointer, code, message, headers, new_url):
        return None


def emit(event):
    print(json.dumps(event), flush=True)


def request(opener, url, data=None, content_type=None):
    headers = {} if content_type is None else {"Content-Type": content_type}
    req = urllib.request.Request(url, data=data, headers=headers)
    try:
        response = opener.open(req, timeout=30)
    except urllib.error.HTTPError as error:
        response = error
    with response:
        return response.code, response.headers, response.read()


def percentile(values, quantile):
    if not values:
        return None
    data = sorted(values)
    index = (len(data) - 1) * quantile
    lower = int(index)
    upper = min(lower + 1, len(data) - 1)
    return round(data[lower] + (data[upper] - data[lower]) * (index - lower), 3)


def client(base, username, password, assignment):
    opener = urllib.request.build_opener(NoRedirect(), urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    code, _, body = request(opener, base + "/index.php?r=auth/login")
    token = re.search(rb'name="_csrf_token" value="([a-f0-9]+)"', body)
    if code != 200 or not token:
        raise RuntimeError("Load client login form unavailable")
    fields = {"identifier": username, "password": password, "_csrf_token": token.group(1).decode()}
    code, _, _ = request(opener, base + "/index.php?r=auth/login", urllib.parse.urlencode(fields).encode(), "application/x-www-form-urlencoded")
    if code != 302:
        raise RuntimeError("Load client login failed")
    route = base + "/index.php?r=student/submitAssignment/" + str(assignment)
    code, _, body = request(opener, route)
    token = re.search(rb'name="_csrf_token" value="([a-f0-9]+)"', body)
    if code != 200 or not token:
        raise RuntimeError("Load client submission form unavailable")
    boundary = "LoadSyntheticMultipartBoundary"
    synthetic = (b"Synthetic load-test reflection. No personal information.\n" * 100)[:4096]
    assert len(synthetic) == 4096
    body = (
        f'--{boundary}\r\nContent-Disposition: form-data; name="_csrf_token"\r\n\r\n{token.group(1).decode()}\r\n'
        f'--{boundary}\r\nContent-Disposition: form-data; name="content"\r\n\r\nSynthetic load reflection\r\n'
        f'--{boundary}\r\nContent-Disposition: form-data; name="file"; filename="reflection.txt"\r\nContent-Type: text/plain\r\n\r\n'
    ).encode() + synthetic + f"\r\n--{boundary}--\r\n".encode()
    return opener, route, body, "multipart/form-data; boundary=" + boundary


def main():
    settings = json.loads(sys.stdin.readline())
    parsed = urllib.parse.urlparse(settings["baseUrl"])
    if parsed.scheme != "http" or parsed.hostname != "127.0.0.1":
        raise RuntimeError("Load target must be disposable loopback")
    smoke = settings["smoke"]
    levels = [1, 2] if smoke else [1, 5, 10, 20]
    durations = [("warmup", 1 if smoke else 30), ("measured", 2 if smoke else 120)]
    output = Path(settings["outputDirectory"])
    clients = [client(settings["baseUrl"], f"load_student_{number:02}", settings["password"], settings["assignmentId"]) for number in range(1, 21)]
    settings.pop("password", None)
    emit({"event": "ready", "python": platform.python_version(), "os": platform.system() + " " + platform.release(), "logicalCpuCount": os.cpu_count(), "payloadBytes": 4096, "levels": levels, "smoke": smoke})
    all_phases = []
    with (output / "requests.csv").open("w", newline="", encoding="utf-8") as csv_file:
        writer = csv.writer(csv_file)
        writer.writerow(["concurrency", "phase", "offsetSeconds", "latencyMilliseconds", "httpStatus", "accepted"])
        for level in levels:
            for phase, duration in durations:
                emit({"event": "phase_start", "concurrency": level, "phase": phase, "durationSeconds": duration})
                records = []
                lock = threading.Lock()
                resources_before = host_resources()
                start = time.perf_counter()
                deadline = start + duration

                def submit(index):
                    opener, route, payload, content_type = clients[index]
                    local = []
                    while time.perf_counter() < deadline:
                        began = time.perf_counter()
                        try:
                            code, headers, _ = request(opener, route, payload, content_type)
                            accepted = code == 302 and headers.get("Location", "").endswith("index.php?r=course/view/1")
                        except (OSError, ValueError):
                            code, accepted = 0, False
                        elapsed = (time.perf_counter() - began) * 1000
                        local.append((round(began - start, 6), elapsed, code, accepted))
                    with lock:
                        records.extend(local)

                with concurrent.futures.ThreadPoolExecutor(max_workers=level) as executor:
                    futures = [executor.submit(submit, index) for index in range(level)]
                    while not all(future.done() for future in futures):
                        concurrent.futures.wait(futures, timeout=min(20, duration))
                        if not all(future.done() for future in futures):
                            emit({"event": "progress", "concurrency": level, "phase": phase, "elapsedSeconds": round(time.perf_counter() - start, 1)})
                    for future in futures:
                        future.result()
                elapsed = time.perf_counter() - start
                latencies = [row[1] for row in records]
                accepted = sum(row[3] for row in records)
                statuses = {}
                for offset, latency, code, success in records:
                    statuses[str(code)] = statuses.get(str(code), 0) + 1
                    writer.writerow([level, phase, offset, round(latency, 3), code, int(success)])
                csv_file.flush()
                resources_after = host_resources()
                resources = {}
                if resources_before and resources_after:
                    total = resources_after["totalTicks"] - resources_before["totalTicks"]
                    idle = resources_after["idleTicks"] - resources_before["idleTicks"]
                    resources = {"hostCpuBusyPercent": round(100 * (total - idle) / total, 2) if total else None, "hostMemoryTotalBytes": resources_after["hostMemoryTotalBytes"], "hostMemoryAvailableStartBytes": resources_before["hostMemoryAvailableBytes"], "hostMemoryAvailableEndBytes": resources_after["hostMemoryAvailableBytes"], "scope": "whole host, includes unrelated activity; app-only process resource usage not instrumented"}
                result = {"concurrency": level, "phase": phase, "targetDurationSeconds": duration, "actualDurationSeconds": round(elapsed, 3), "requests": len(records), "accepted": accepted, "errors": len(records) - accepted, "throughputPerSecond": round(accepted / elapsed, 3), "latencyMilliseconds": {"p50": percentile(latencies, .5), "p95": percentile(latencies, .95), "p99": percentile(latencies, .99), "max": round(max(latencies), 3) if latencies else None}, "httpStatuses": statuses, "resources": resources}
                all_phases.append(result)
                emit({"event": "phase_end", "result": result})
                # Parent snapshots the durable database while all HTTP clients are stopped.
                if sys.stdin.readline().strip() != "continue":
                    raise RuntimeError("Load orchestrator stopped")
    emit({"event": "complete", "phases": all_phases})


if __name__ == "__main__":
    try:
        main()
    except Exception as error:
        emit({"event": "error", "reason": type(error).__name__})
        raise SystemExit(1)
