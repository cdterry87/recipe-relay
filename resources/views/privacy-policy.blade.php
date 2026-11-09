<x-layouts::app title="Privacy Policy" reading>
    <x-ui.card class="legal">
        <header class="legal__header">
            <h1>Privacy Policy</h1>
            <p class="legal__meta">Effective November 9, 2026</p>
        </header>

        <x-ui.notice tone="info">
            {{ config('app.name') }} is a portfolio application that is not intended for public use.
            Please don't share anything you'd want to keep private.
        </x-ui.notice>

        <p>This policy explains what {{ config('app.name') }} stores, why, and who else sees it. By using {{ config('app.name') }}, you agree to it.</p>

        <section>
            <h2>1. About this application</h2>
            <p>{{ config('app.name') }} is a portfolio project by <a href="https://chaseterry.com" rel="noopener" target="_blank">Chase Terry<span class="visually-hidden"> (opens in a new tab)</span></a>. There is no support mailbox. A shared demo account is available, and its data is reset every night.</p>
        </section>

        <section>
            <h2>2. Information we collect</h2>
            <ul>
                <li><strong>Account details:</strong> your name, email address, password (stored only as a secure hash), and timezone. If you sign in with Google, we also receive your Google name, email address and profile picture.</li>
                <li><strong>Your content:</strong> your profile, recipes and photos, the recipes you save, your ratings, and the creators you follow.</li>
                <li><strong>Technical information:</strong> your IP address and browser details, used to limit repeated sign-in attempts, check for bots, and keep server logs.</li>
            </ul>
        </section>

        <section>
            <h2>3. How we use your information</h2>
            <p>Only to run {{ config('app.name') }}: to show your recipes to the people you choose (only you for private recipes, anyone with the link for unlisted ones, and everyone for public ones), to send the emails you ask for or opt into, such as password resets and the weekly digest, and to protect the application from abuse. There are no analytics or advertising.</p>
        </section>

        <section>
            <h2>4. Information sharing</h2>
            <p>Your information is never sold or traded. It is shared only with the services that run {{ config('app.name') }}, or when the law requires it:</p>
            <ul>
                <li><strong>Cloudflare Turnstile</strong> checks sign-up and the demo login for bots, and receives your IP address and browser signals to do so.</li>
                <li>Our <strong>email provider</strong> delivers the emails you ask for, and receives your email address to do so.</li>
                <li><strong>Google</strong>, only if you choose to sign in with Google.</li>
                <li>The <strong>hosting provider</strong> stores the application and its database.</li>
            </ul>
            <p>Share buttons for social networks send nothing until you click one, and then only the recipe's link goes to that network.</p>
        </section>

        <section>
            <h2>5. Cookies and local storage</h2>
            <p>{{ config('app.name') }} uses a session cookie and a security token cookie to keep you signed in and protect forms, and a "remember me" cookie only if you choose it. Your light or dark theme choice is kept in your browser's local storage. Cloudflare Turnstile may set its own cookies on the pages where it runs. There are no tracking cookies.</p>
        </section>

        <section>
            <h2>6. Retention and deletion</h2>
            <p>Your information is kept until you delete your account, which removes your account, recipes, saves, ratings and follows. No method of storage or transfer is completely secure, but passwords are hashed, connections are encrypted, and sign-in attempts are rate limited.</p>
        </section>

        <section>
            <h2>7. Children's privacy</h2>
            <p>{{ config('app.name') }} is not intended for children under 13, and we do not knowingly collect information from them.</p>
        </section>

        <section>
            <h2>8. Changes to this policy</h2>
            <p>This policy may change. The effective date above shows when it last did.</p>
        </section>

        <p><a href="{{ url('/') }}">Back to {{ config('app.name') }}</a></p>
    </x-ui.card>
</x-layouts::app>
