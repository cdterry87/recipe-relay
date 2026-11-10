You have a new follower on {!! config('app.name') !!}

Hi {!! $name !!},

{!! $actor['name'] !!} started following you. Your new public recipes will show up in their feed and weekly digest.

See their profile: {!! route('creators.show', $actor['uuid']) !!}

Email settings: {!! $settingsUrl !!}
Unsubscribe: {!! $unsubscribeUrl !!}
