@props(['record'])
{{-- Grading "media / cover" as short German labels for the PDF exports. --}}
{{ $record->gradingMedia()['german'] ?? '–' }} / {{ $record->gradingCover()['german'] ?? '–' }}
