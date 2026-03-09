@props(['plan', 'isFavorited'])
<div x-data="{
    fav: {{ $isFavorited ? 'true' : 'false' }},
    toggle() {
        fetch('{{ route('favorites.toggle', $plan) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json'
            }
        }).then(r => {
            if (!r.ok) return;
            r.json().then(d => { this.fav = d.favorited; }).catch(() => {});
        }).catch(() => {});
    }
}">
    <button @click="toggle" title="Toggle favorite"
            :class="fav ? 'text-yellow-400 hover:text-yellow-500' : 'text-gray-300 hover:text-yellow-400'"
            class="text-lg leading-none transition-colors">★</button>
</div>
