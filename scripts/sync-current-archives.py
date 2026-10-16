#!/usr/bin/env python3
"""Read every public page in KZFR's current Creek archive catalogue.

The endpoint and filters are used by the official /archives/ application.
No authentication, audio download, form submission or WordPress mutation occurs.
An existing private station inventory is required. Output is a separate review
file; source text and titles are preserved. Historical legacy HTML metadata is
not equivalent to currently available recordings and is not crawled here.
"""
import argparse
from datetime import datetime
import hashlib
import html
import json
import os
from pathlib import Path
import re
import subprocess
import tempfile
import time
from urllib.parse import urlparse, parse_qs
from zoneinfo import ZoneInfo

API = 'https://kzfr.studio.creek.org/api/archives'
SHOWS = API + '/shows-list'
# Exact reviewed equivalences between the modern provider's show IDs/titles and
# the legacy station directory. These are explicit aliases, not fuzzy matching.
ALIASES = {
    2362: 'fivemile', 2287: 'dinosaurhour', 2302: 'electronic-phonics',
    2339: 'the-fun-house', 2264: 'funky-reservation', 2337: 'ocean',
    2265: 'randompick', 2702: 'rootsandculture',
    2379: 'talkin-bout-practice', 2385: 'doctor',
}


def normalized_title(value):
    return re.sub(r'[^a-z0-9]', '', value.lower())


def https_url(value):
    if not isinstance(value, str):
        return ''
    parsed = urlparse(value)
    return value if parsed.scheme == 'https' and parsed.hostname and not parsed.username and not parsed.password else ''


def allowed_endpoint(url):
    parsed = urlparse(url)
    if parsed.scheme != 'https' or parsed.netloc != 'kzfr.studio.creek.org' or parsed.fragment:
        return False
    if parsed.path == '/api/archives/shows-list':
        return not parsed.query
    if parsed.path != '/api/archives':
        return False
    query = parse_qs(parsed.query)
    return not query or (set(query) == {'page'} and len(query['page']) == 1 and query['page'][0].isdigit())


def synchronize(data, cache, timestamp):
    manifest = []

    def fetch(url):
        if not allowed_endpoint(url):
            raise ValueError('Unexpected pagination destination: ' + url)
        target = cache / (hashlib.sha256(url.encode()).hexdigest() + '.json')
        # Two attempts for a transient public endpoint error. No TLS bypass.
        for attempt in range(2):
            result = subprocess.run(['curl', '--fail', '--silent', '--show-error', '--location', '--proto', '=https', '--proto-redir', '=https', '--max-time', '30', url, '--output', str(target)], capture_output=True, text=True)
            if result.returncode == 0:
                break
            if attempt == 0:
                time.sleep(1)
        if result.returncode:
            raise RuntimeError(f'Public archive request failed: {url}: {result.stderr.strip()}')
        raw = target.read_bytes()
        response = json.loads(raw)
        if not isinstance(response, dict) or not isinstance(response.get('data'), list):
            raise ValueError('Unexpected public archive response: ' + url)
        manifest.append({'url': url, 'retrieved_at': timestamp, 'sha256': hashlib.sha256(raw).hexdigest(), 'method': 'Official archive application public HTTPS endpoint; no authentication; audio not downloaded'})
        return response

    show_response = fetch(SHOWS)
    show_records = show_response['data']
    if not show_records or len({row.get('id') for row in show_records}) != len(show_records):
        raise ValueError('Provider show directory is empty or contains duplicate IDs.')
    records = []
    expected = None
    pages = []
    url = API
    seen_urls = set()
    page_by_record = {}
    while url:
        if url in seen_urls:
            raise ValueError('Pagination loop detected.')
        seen_urls.add(url)
        response = fetch(url)
        meta = response.get('meta', {})
        total = meta.get('total')
        if not isinstance(total, int) or total <= 0:
            raise ValueError('Provider total is missing, invalid or empty; preserve the previous catalogue.')
        if expected is None:
            expected = total
        if total != expected:
            raise ValueError('Catalogue changed during pagination; retry the complete refresh.')
        pages.append(meta.get('current_page'))
        for row in response['data']:
            page_by_record[row['id']] = url
        records.extend(response['data'])
        url = response.get('links', {}).get('next')
        if url:
            time.sleep(.25)
    if len(records) != expected or len({row['id'] for row in records}) != expected:
        raise ValueError('Catalogue count/uniqueness check failed. Do not publish a partial refresh.')
    if pages != list(range(1, len(pages) + 1)):
        raise ValueError('Catalogue page coverage is not contiguous.')

    programs = {record['slug']: dict(record) for record in data['programs']}
    exact_titles = {}
    for program in programs.values():
        exact_titles.setdefault(normalized_title(program['title']), []).append(program['slug'])
    detailed_shows = {row['show_id']: row['show'] for row in records if row.get('show')}
    mapping = {}
    new_slugs = []
    for show in show_records:
        provider_id = show['id']
        title = show['title']
        existing_by_provider = [p['slug'] for p in programs.values() if provider_id in p.get('provider_show_ids', [])]
        if len(existing_by_provider) == 1:
            key = existing_by_provider[0]
            match_method = 'existing provider ID'
        elif provider_id in ALIASES:
            key = ALIASES[provider_id]
            if key not in programs:
                raise ValueError('Reviewed alias target missing: ' + key)
            match_method = 'explicit reviewed provider/legacy alias'
        else:
            candidates = exact_titles.get(normalized_title(title), [])
            if len(candidates) > 1:
                raise ValueError('Ambiguous existing title: ' + title)
            key = candidates[0] if candidates else 'creek-show-' + str(provider_id)
            match_method = 'exact title after case/punctuation normalization' if candidates else 'new archive-directory record preserving provider ID'
        detail = detailed_shows.get(provider_id, {})
        archive = 'https://kzfr.studio.creek.org/archives/?showId=' + str(provider_id)
        official = https_url(show.get('share', {}).get('url')) or archive
        if key not in programs:
            summary = 'Listed in KZFR’s official archive program directory. This program is not listed in the current weekly schedule.'
            programs[key] = {'slug': key, 'title': title, 'summary': summary, 'content': '<p>' + html.escape(summary) + '</p><p><a href="' + archive + '">Check the official archive</a> for available recordings.</p>', 'source_url': official, 'host': '', 'hosts': [], 'schedule': 'Not listed in the current weekly schedule.', 'schedule_slots': [], 'archive_url': archive, 'playlist_url': '', 'genres': [], 'image_url': '', 'source_image_url': https_url((show.get('image') or {}).get('url')), 'archive_only': True, 'content_status': 'Official provider directory record; no current weekly airtime inferred.'}
            new_slugs.append(key)
        program = programs[key]
        provider_ids = program.setdefault('provider_show_ids', [])
        if provider_id not in provider_ids:
            provider_ids.append(provider_id)
        if not program.get('archive_only'):
            program['archive_only'] = False
        alias = {'provider_show_id': provider_id, 'provider_title': title, 'provider_name': detail.get('name', ''), 'program_slug': key, 'match_method': match_method, 'source_url': SHOWS}
        mapping[str(provider_id)] = alias
        aliases = program.setdefault('provider_aliases', [])
        aliases[:] = [a for a in aliases if a.get('provider_show_id') != provider_id]
        aliases.append(alias)
        # Preserve editorial descriptions and existing legacy archive routes.
        program['provider_archive_url'] = archive

    broadcasts = []
    excluded = []
    for row in records:
        audio = https_url((row.get('audio') or {}).get('url'))
        source = https_url((row.get('share') or {}).get('url'))
        if row.get('hidden_at') or row.get('plus_is_members_only') or not audio or not source or urlparse(audio).hostname != 'kzfr-media.s3.us-west-000.backblazeb2.com' or urlparse(source).hostname != 'studio-share.creek.org':
            excluded.append({'provider_id': row['id'], 'reason': 'Not an ordinary public playable record with verified HTTPS destinations.'})
            continue
        if str(row['show_id']) not in mapping:
            raise ValueError('Broadcast references a show absent from provider directory.')
        key = mapping[str(row['show_id'])]['program_slug']
        program = programs[key]
        start = datetime.fromisoformat(row['start'].replace('Z', '+00:00')).astimezone(ZoneInfo('America/Los_Angeles'))
        end = datetime.fromisoformat(row['end'].replace('Z', '+00:00')).astimezone(ZoneInfo('America/Los_Angeles'))
        provider_title = row.get('title')
        title = program['title'] + ' · ' + start.strftime('%B %-d, %Y')
        content = '<p>' + html.escape(program['title']) + ', ' + start.strftime('%B %-d, %Y at %-I:%M %p') + ' Pacific.</p><p><a href="' + source + '">Open the official KZFR recording</a>. Availability is controlled by the station and may change.</p>'
        broadcasts.append({'slug': 'creek-broadcast-' + str(row['id']), 'title': title, 'content': content, 'source_url': source, 'broadcast_date': start.date().isoformat(), 'broadcast_time': start.strftime('%H:%M'), 'broadcast_end': end.strftime('%H:%M'), 'program_slug': key, 'genres': list(program.get('genres', [])), 'audio_url': audio, 'disable_seek': bool((row.get('show') or {}).get('archives_disable_seek')), 'provider_id': row['id'], 'provider_show_id': row['show_id'], 'provider_title': provider_title or '', 'start_utc': row['start'], 'end_utc': row['end'], 'metadata_source_url': page_by_record[row['id']], 'playback_status': 'Official public catalogue supplied an HTTPS audio URL at retrieval.', 'provider_archives_disable': bool((row.get('show') or {}).get('archives_disable')), 'provider_members_only': False})
    if excluded:
        raise ValueError('Catalogue contains excluded or unexpected-host records; manual review required before replacement: ' + json.dumps(excluded))
    data['programs'] = list(programs.values())
    data['broadcasts'] = broadcasts
    data['provider_program_map'] = mapping
    data['archive_coverage'] = {'provider': 'KZFR Creek public archive catalogue', 'endpoint': API, 'show_directory_endpoint': SHOWS, 'retrieved_at': timestamp, 'provider_total': expected, 'retrieved_unique_records': len(records), 'imported_playable_records': len(broadcasts), 'excluded_records': excluded, 'pages_checked': pages, 'complete_current_public_catalogue': len(records) == expected and not excluded, 'provider_show_records': len(show_records), 'mapped_program_records': len(programs), 'added_archive_directory_records': new_slugs, 'date_min_pacific': min(b['broadcast_date'] for b in broadcasts) if broadcasts else None, 'date_max_pacific': max(b['broadcast_date'] for b in broadcasts) if broadcasts else None, 'seek_disabled_records': sum(b['disable_seek'] for b in broadcasts), 'legacy_history_note': 'Historical legacy /shows pagination contains older metadata whose recordings are hidden or unavailable. It is not part of the provider’s current playable catalogue and is not represented as migrated audio.', 'audio_note': 'Audio remains on the provider HTTPS host. No audio is downloaded or rehosted. Preserve the provider seek restriction.'}
    data['sources'] = [s for s in data.get('sources', []) if s.get('url') not in {m['url'] for m in manifest}] + manifest
    for issue in data.get('uncertainties', []):
        if issue.get('id') == 'archive-window':
            issue['detail'] = 'All ' + str(expected) + ' records in the current public Creek catalogue were retrieved across ' + str(len(pages)) + ' pages. Availability can change. Historical legacy metadata outside this playable catalogue has not been migrated.'
        elif issue.get('id') == 'creek-api':
            issue['detail'] = 'The public archive application exposes /api/archives and /api/archives/shows-list. All pagination links are followed and provider counts validated. This is a read-only snapshot; background synchronization is not automatically enabled.'
    data.setdefault('integrations', []).append({'name': 'Current Creek catalogue', 'url': API, 'mode': 'All public pages read, count validated; read-only source refresh; provider-hosted audio and seek restrictions preserved'}) if not any(i.get('url') == API for i in data.get('integrations', [])) else None
    return data


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--source', type=Path, required=True)
    parser.add_argument('--output', type=Path, required=True)
    parser.add_argument('--cache', type=Path, help='Private evidence cache; keep out of public source repository.')
    args = parser.parse_args()
    if args.source.resolve() == args.output.resolve():
        parser.error('Output must differ from source; review the result before import.')
    data = json.loads(args.source.read_text())
    temp = tempfile.TemporaryDirectory(prefix='kzfr-creek-') if not args.cache else None
    cache = args.cache or Path(temp.name)
    cache.mkdir(parents=True, exist_ok=True)
    timestamp = datetime.now(ZoneInfo('America/Los_Angeles')).isoformat(timespec='seconds')
    result = synchronize(data, cache, timestamp)
    args.output.parent.mkdir(parents=True, exist_ok=True)
    with tempfile.NamedTemporaryFile('w', encoding='utf-8', dir=args.output.parent, prefix=args.output.name + '.', suffix='.tmp', delete=False) as handle:
        pending = Path(handle.name)
        handle.write(json.dumps(result, ensure_ascii=False, indent=2) + '\n')
        handle.flush()
        os.fsync(handle.fileno())
    os.replace(pending, args.output)
    print(json.dumps(result['archive_coverage'], ensure_ascii=False))
    if temp:
        temp.cleanup()


if __name__ == '__main__':
    main()
