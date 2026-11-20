from http.server import BaseHTTPRequestHandler
from urllib.parse import urlparse, parse_qs
import json
import time

# Pre-allocated 512KB chunk for fast serverless download responses
DOWNLOAD_CHUNK = b"0123456789ABCDEF" * (32 * 1024)  # 512 KB


class handler(BaseHTTPRequestHandler):
    def _set_common_headers(self, content_type="application/json", status=200):
        self.send_response(status)
        self.send_header("Content-Type", content_type)
        self.send_header("Cache-Control", "no-store, no-cache, must-revalidate, max-age=0")
        self.send_header("Pragma", "no-cache")
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type")

    def do_OPTIONS(self):
        self._set_common_headers(status=204)
        self.end_headers()

    def do_GET(self):
        parsed = urlparse(self.path)
        params = parse_qs(parsed.query)
        action = params.get("action", ["ping"])[0]

        if action == "download":
            # 1MB per request (2 x 512KB) keeps serverless latency & response size clean
            payload = DOWNLOAD_CHUNK * 2
            self._set_common_headers(content_type="application/octet-stream", status=200)
            self.send_header("Content-Length", str(len(payload)))
            self.end_headers()
            self.wfile.write(payload)
            return

        body = json.dumps({"status": "pong", "ts": time.time()}).encode("utf-8")
        self._set_common_headers(content_type="application/json", status=200)
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def do_POST(self):
        content_length = int(self.headers.get("Content-Length", "0"))
        remaining = max(0, content_length)
        received = 0

        while remaining > 0:
            chunk = self.rfile.read(min(65536, remaining))
            if not chunk:
                break
            received += len(chunk)
            remaining -= len(chunk)

        body = json.dumps({"received": received}).encode("utf-8")
        self._set_common_headers(content_type="application/json", status=200)
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)
