#!/usr/bin/env python3
"""Reviewable refresh of KZFR public schedule and archive metadata.

Requires Python 3.9+, BeautifulSoup4, curl, and an existing private inventory.
This reads public HTTPS pages only. It never submits forms, downloads audio,
bypasses TLS, publishes content, or imports into WordPress. Review the output
before running import-content.php. Curated bodies and staff copy are preserved.
"""
import argparse
import concurrent.futures
from datetime import datetime, timedelta
import hashlib
import html
import json
from pathlib import Path
import re
import runpy
import subprocess
import tempfile
from urllib.parse import urljoin
from zoneinfo import ZoneInfo

from bs4 import BeautifulSoup

BASE = 'https://kzfr.org'
DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']


def text(node):
    return re.sub(r'\s+', ' ', node.get_text(' ', strip=True)).strip() if node else ''


def hhmm(value):
    return datetime.strptime(value, '%I:%M%p').strftime('%H:%M')


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--source', type=Path, default=Path(__file__).resolve().parents[1] / 'content/station-content.json')
    parser.add_argument('--output', type=Path, required=True, help='Separate review file; may not overwrite source.')
    parser.add_argument('--as-of', default=datetime.now(ZoneInfo('America/Los_Angeles')).date().isoformat())
    parser.add_argument('--cache', type=Path, help='Optional persistent restricted directory for public source HTML.')
    args = parser.parse_args()
    if args.output.resolve() == args.source.resolve():
        parser.error('Choose a separate --output review file. Preserve the source inventory.')
    if not args.source.is_file():
        parser.error('An existing private inventory is required. Restore it from the private deployment backup or export, not a public repository.')
    as_of = datetime.strptime(args.as_of, '%Y-%m-%d').date()
    data = json.loads(args.source.read_text())
    if data.get('version') != 1:
        parser.error('Unsupported inventory version.')
    old = {x['slug']: x for x in data['programs']}
    temporary = tempfile.TemporaryDirectory(prefix='kzfr-public-') if not args.cache else None
    cache = args.cache or Path(temporary.name)
    cache.mkdir(parents=True, exist_ok=True)
    sources = []

    def fetch(path):
        if not re.fullmatch(r'/shows/(?:index/)?[a-z0-9-]+', path):
            raise ValueError('Unapproved source path: ' + path)
        destination = cache / (path.strip('/').replace('/', '_') + '.html')
        result = subprocess.run(['curl', '--fail', '--silent', '--show-error', '--location', '--proto', '=https', '--proto-redir', '=https', '--max-time', '25', '--user-agent', 'Mozilla/5.0 KZFR-public-inventory-review', BASE + path, '--output', str(destination)], capture_output=True, text=True)
        if result.returncode:
            raise RuntimeError(f'Cannot retrieve {path}: {result.stderr.strip()}')
        raw = destination.read_bytes()
        parsed = BeautifulSoup(raw, 'html.parser')
        if not parsed.select_one('#main'):
            raise RuntimeError('Unexpected response layout for ' + path)
        return parsed, {'url': BASE + path, 'retrieved_at': args.as_of, 'sha256': hashlib.sha256(raw).hexdigest(), 'method': 'Public HTTPS HTML; TLS enabled; read only'}

    with concurrent.futures.ThreadPoolExecutor(max_workers=3) as executor:
        daily = list(executor.map(fetch, ['/shows/index/' + day.lower() for day in DAYS]))
    schedule = []
    slugs = set()
    for day, (parsed, source) in zip(DAYS, daily):
        sources.append(source)
        rows = parsed.select('.shows-this-weekday > .program')
        if not rows:
            raise RuntimeError('No schedule rows: ' + day)
        for row in rows:
            named = {}
            for anchor in row.select('.title a'):
                if anchor.find('a'):
                    continue
                path = anchor.get('href', '')
                if re.fullmatch(r'/shows/[a-z0-9-]+', path) and text(anchor):
                    named[path.rsplit('/', 1)[-1]] = text(anchor)
            start = hhmm(text(row.select_one('.start')))
            end = hhmm(text(row.select_one('.end')))
            unnamed = any(a.get('href') == '/shows/' for a in row.select('.title a'))
            slot = {'day': day, 'start': start, 'end': end, 'program_slug': next(iter(named), ''), 'program_title': ' / '.join(named.values()) or 'Program not named in public schedule', 'program_slugs': list(named), 'program_titles': list(named.values()), 'rotation': 'Multiple programs share this slot; check KZFR for the current rotation.' if len(named) > 1 else '', 'note': 'The public source includes an unnamed rotation entry.' if unnamed else '', 'source_url': source['url'], 'start_day_offset': int(start < '05:00'), 'end_day_offset': int(end <= start or start < '05:00')}
            schedule.append(slot)
            slugs.update(named)
    with concurrent.futures.ThreadPoolExecutor(max_workers=3) as executor:
        detail_results = list(executor.map(fetch, ['/shows/' + key for key in sorted(slugs)]))
    programs = []
    broadcasts = []
    for key, (parsed, source) in zip(sorted(slugs), detail_results):
        sources.append(source)
        program = parsed.select_one('#program')
        if not program or not program.select_one('h1'):
            raise RuntimeError('No program detail: ' + key)
        record = dict(old.get(key, {}))
        title = text(program.select_one('h1'))
        hosts = [text(a) for a in program.select('.host-link')]
        genres = list(dict.fromkeys(text(a) for a in program.select('.category-link')))
        slots = [row for row in schedule if key in row['program_slugs']]
        schedule_text = '; '.join(row['day'] + ' ' + row['start'] + '–' + row['end'] + (' (rotation)' if row['rotation'] else '') for row in slots) + ' Pacific'
        record.update({'slug': key, 'title': title, 'source_url': source['url'], 'host': ', '.join(hosts), 'hosts': hosts, 'genres': genres, 'schedule': schedule_text, 'schedule_slots': slots})
        record.setdefault('summary', 'Find current information on this program’s official KZFR page.')
        record.setdefault('content', '<p>' + html.escape(record['summary']) + '</p><p><a href="' + source['url'] + '">Visit the official program page</a>.</p>')
        record.setdefault('archive_url', source['url'])
        record.setdefault('playlist_url', '')
        record.setdefault('image_url', '')
        image = program.select_one('.image img')
        record['source_image_url'] = image.get('src', '') if image else ''
        record['content_status'] = 'Public metadata refreshed; preserved text may contain an older date. Staff review required.'
        programs.append(record)
    missing = sorted(set(old) - slugs)
    # Preserve former program records for review instead of silently deleting them.
    for key in missing:
        record = dict(old[key])
        record['content_status'] = 'Not found in refreshed weekly directory; review before publishing.'
        record['schedule_slots'] = []
        programs.append(record)
    data.update({'fetched_at': args.as_of, 'programs': programs, 'schedule': schedule})
    refreshed_urls = {source['url'] for source in sources}
    data['sources'] = [source for source in data.get('sources', []) if source.get('url') not in refreshed_urls] + sources
    data['refresh_report'] = {'review_required': True, 'as_of': args.as_of, 'programs_new': sorted(slugs - set(old)), 'programs_not_in_weekly_directory': missing, 'preserved_sections': ['pages', 'events', 'underwriters', 'integrations', 'station', 'uncertainties'], 'note': 'Only schedule/program facts and recent archive metadata refreshed. Verify preserved page dates, event status, underwriter participation and staff edits before import.'}
    catalogue_cache = cache / 'current-catalogue'
    catalogue_cache.mkdir(parents=True, exist_ok=True)
    synchronize = runpy.run_path(str(Path(__file__).with_name('sync-current-archives.py')))['synchronize']
    data = synchronize(data, catalogue_cache, datetime.now(ZoneInfo('America/Los_Angeles')).isoformat(timespec='seconds'))
    programs = data['programs']
    broadcasts = data['broadcasts']
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(data, indent=2, ensure_ascii=False) + '\n')
    print(json.dumps({'output': str(args.output), 'programs': len(programs), 'schedule': len(schedule), 'broadcasts': len(broadcasts), 'new_programs': len(slugs - set(old)), 'not_in_directory': len(missing), 'review_required': True}))
    if temporary:
        temporary.cleanup()


if __name__ == '__main__':
    main()
