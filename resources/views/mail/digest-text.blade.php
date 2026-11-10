Your weekly digest from {!! config('app.name') !!}

Hi {!! $name !!},

@if ($digest->isEmpty())
Nobody you follow has shared a new recipe since your last digest. Here's what's popular instead.
@else
Here's what the cooks you follow have been making this week.
@foreach ($digest->creators as $entry)

{!! $entry['creator']->name !!}
@foreach ($entry['recipes'] as $recipe)
- {!! $recipe->title !!}: {!! $recipe->shareUrl() !!}
@endforeach
@if ($entry['more'] > 0)
- and {!! $entry['more'] !!} more: {!! route('creators.show', $entry['creator']) !!}
@endif
@endforeach
@endif
@if ($digest->trending->isNotEmpty())

Trending this week
@foreach ($digest->trending as $recipe)
- {!! $recipe->title !!} by {!! $recipe->user->name !!}: {!! $recipe->shareUrl() !!}
@endforeach
@endif

See your feed: {!! route('my.following') !!}

Change when this arrives: {!! $settingsUrl !!}
Unsubscribe: {!! $unsubscribeUrl !!}
