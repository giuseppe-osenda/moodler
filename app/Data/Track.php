<?php

namespace App\Data;

/**
 * A Spotify track that can be played in the embed player.
 */
final readonly class Track
{
    public function __construct(
        public string $id,
        public string $title,
        public string $artists,
        public string $album,
        public ?string $imageUrl,
        public int $durationMs,
    ) {}

    public function uri(): string
    {
        return "spotify:track:{$this->id}";
    }

    public function url(): string
    {
        return "https://open.spotify.com/track/{$this->id}";
    }

    /**
     * Build a track from a Spotify Web API track object.
     *
     * @param  array<string, mixed>  $item
     */
    public static function fromSpotify(array $item): self
    {
        $images = $item['album']['images'] ?? [];
        $image = collect($images)->sortBy('width')->first(fn (array $image): bool => ($image['width'] ?? 0) >= 120)
            ?? ($images[0] ?? null);

        return new self(
            id: (string) $item['id'],
            title: (string) ($item['name'] ?? ''),
            artists: collect($item['artists'] ?? [])->pluck('name')->implode(', '),
            album: (string) ($item['album']['name'] ?? ''),
            imageUrl: isset($image['url']) ? (string) $image['url'] : null,
            durationMs: (int) ($item['duration_ms'] ?? 0),
        );
    }

    /**
     * @return array{id: string, uri: string, url: string, title: string, artists: string, album: string, imageUrl: ?string, durationMs: int}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uri' => $this->uri(),
            'url' => $this->url(),
            'title' => $this->title,
            'artists' => $this->artists,
            'album' => $this->album,
            'imageUrl' => $this->imageUrl,
            'durationMs' => $this->durationMs,
        ];
    }
}
