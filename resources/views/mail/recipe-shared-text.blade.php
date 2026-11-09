{!! $sender !!} shared a recipe with you on {!! config('app.name') !!}

@if ($note)
"{!! $note !!}"

@endif
{!! $recipe->title !!} by {!! $recipe->user->name !!}
@if ($recipe->summary)
{!! $recipe->summary !!}
@endif

Open the recipe: {!! $url !!}

{!! $sender !!} sent this from {!! config('app.name') !!}. We don't keep your address or add you to any list.
