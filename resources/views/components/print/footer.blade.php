<footer class="bg-white w-full h-auto text-center fixed">
    <div class="footer-content text-2xs leading-3">
        <div class="absolute left-0 right-0 m-auto max-h-32 px-6">
            <img class="logo-small m-auto footer-logo" src="{{ $client->logo_small }}" />
        </div>
        <div class="w-full">
            <div class="border-t border-semi-black">
                <address class="text-left not-italic float-left">
                    <div class="font-semibold">
                        {{ $client->name ?? '' }}
                    </div>
                    <div>
                        {{ $client->ceo ?? '' }}
                    </div>
                    <div>
                        {{ $client->street ?? '' }}
                    </div>
                    <div>
                        {{ trim(($client->postcode ?? '') . ' ' . ($client->city ?? '')) }}
                    </div>
                    <div>
                        {{ $client->phone ?? '' }}
                    </div>
                </address>
                <div class="clear-both"></div>
            </div>
        </div>
    </div>
</footer>
