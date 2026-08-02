#!/usr/bin/env python3
"""Non-destructive, dry-run-first migration of ZeroWaste media to S3."""
from __future__ import annotations

import argparse
import hashlib
import json
import mimetypes
import os
from pathlib import Path

CATEGORIES = ("foro", "perfiles", "recompensas", "puntos", "campañas", "eventos", "qr")


def digest(path: Path) -> str:
    value = hashlib.sha256()
    with path.open("rb") as source:
        for block in iter(lambda: source.read(1024 * 1024), b""):
            value.update(block)
    return value.hexdigest()


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("source", type=Path)
    parser.add_argument("--apply", action="store_true", help="Upload; default is dry-run")
    parser.add_argument("--report", type=Path, default=Path("media-migration-report.json"))
    args = parser.parse_args()
    required = ("MEDIA_S3_ENDPOINT", "MEDIA_S3_REGION", "MEDIA_S3_BUCKET", "MEDIA_S3_ACCESS_KEY", "MEDIA_S3_SECRET_KEY")
    missing = [name for name in required if not os.getenv(name)]
    if missing:
        raise SystemExit("Missing required S3 configuration: " + ", ".join(missing))

    files = [p for category in CATEGORIES for p in (args.source / category).rglob("*") if p.is_file()]
    report = {"mode": "apply" if args.apply else "dry-run", "source": str(args.source), "items": [], "deleted": 0}
    client = None
    if args.apply:
        import boto3
        from botocore.config import Config
        client = boto3.client("s3", endpoint_url=os.environ["MEDIA_S3_ENDPOINT"], region_name=os.environ["MEDIA_S3_REGION"], aws_access_key_id=os.environ["MEDIA_S3_ACCESS_KEY"], aws_secret_access_key=os.environ["MEDIA_S3_SECRET_KEY"], config=Config(retries={"max_attempts": 5, "mode": "standard"}))

    seen: dict[str, str] = {}
    for path in files:
        checksum = digest(path)
        key = path.relative_to(args.source).as_posix()
        item = {"key": key, "sha256": checksum, "size": path.stat().st_size, "duplicate_of": seen.get(checksum), "status": "planned"}
        if not item["duplicate_of"] and client:
            client.upload_file(str(path), os.environ["MEDIA_S3_BUCKET"], key, ExtraArgs={"ContentType": mimetypes.guess_type(path.name)[0] or "application/octet-stream", "Metadata": {"sha256": checksum}})
            item["status"] = "uploaded"
        seen.setdefault(checksum, key)
        report["items"].append(item)
    args.report.write_text(json.dumps(report, indent=2, ensure_ascii=False), encoding="utf-8")
    print(f"mode={report['mode']} files={len(files)} duplicates={sum(bool(i['duplicate_of']) for i in report['items'])} deleted=0 report={args.report}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
