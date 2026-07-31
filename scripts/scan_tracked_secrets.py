#!/usr/bin/env python3
"""Escaneo local de alta confianza que nunca imprime el valor coincidente."""

from __future__ import annotations

import json
import re
import subprocess
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SELF = Path(__file__).resolve()
PATTERNS = {
    "private_key": re.compile(r"-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----"),
    "aws_access_key": re.compile(r"\b(?:AKIA|ASIA)[A-Z0-9]{16}\b"),
    "mapbox_token_literal": re.compile(r"\bpk\.[A-Za-z0-9_-]{20,}\b"),
    "jwt_literal": re.compile(r"\beyJ[A-Za-z0-9_-]{20,}\.[A-Za-z0-9_-]{20,}\.[A-Za-z0-9_-]{10,}\b"),
    "database_url_credentials": re.compile(
        r"(?:postgres(?:ql)?|mysql)://[^\s/:]+:[^\s/@]+@[^\s]+", re.IGNORECASE
    ),
    "hardcoded_sensitive_fallback": re.compile(
        r"(?:SYSTEM_API_KEY|JWT_SECRET_KEY|SECRET_KEY|APP_KEY).{0,100}"
        r"(?:getenv|env)\s*\([^\n]*,\s*['\"][^'\"]{8,}['\"]",
        re.IGNORECASE,
    ),
}
TEXT_SUFFIXES = {
    ".conf", ".css", ".env", ".example", ".html", ".ini", ".js", ".json",
    ".md", ".php", ".py", ".sh", ".sql", ".toml", ".txt", ".xml", ".yaml", ".yml",
}


def tracked_files() -> list[Path]:
    result = subprocess.run(
        ["git", "ls-files", "-z", "--cached", "--others", "--exclude-standard"],
        cwd=ROOT,
        check=True,
        capture_output=True,
    )
    return [ROOT / raw.decode("utf-8") for raw in result.stdout.split(b"\0") if raw]


def main() -> int:
    findings: list[dict[str, object]] = []
    for path in tracked_files():
        if path.resolve() == SELF or path.suffix.lower() not in TEXT_SUFFIXES:
            continue
        try:
            text = path.read_text(encoding="utf-8")
        except (OSError, UnicodeDecodeError):
            continue
        for line_number, line in enumerate(text.splitlines(), start=1):
            for category, pattern in PATTERNS.items():
                if pattern.search(line):
                    findings.append(
                        {
                            "file": path.relative_to(ROOT).as_posix(),
                            "line": line_number,
                            "category": category,
                        }
                    )

    print(json.dumps({"values_redacted": True, "findings": findings}, indent=2))
    return 1 if findings else 0


if __name__ == "__main__":
    raise SystemExit(main())
