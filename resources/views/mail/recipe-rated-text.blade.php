Your recipe got a rating on {!! config('app.name') !!}

Hi {!! $name !!},

{!! $actor['name'] !!} saved {!! $recipe['title'] !!} to their cookbook and gave it {!! $stars !!} out of 5 stars.

View your recipe: {!! route('recipes.show', $recipe['uuid']) !!}

Email settings: {!! $settingsUrl !!}
Unsubscribe: {!! $unsubscribeUrl !!}
