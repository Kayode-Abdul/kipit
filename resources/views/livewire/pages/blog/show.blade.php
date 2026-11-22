<?php

use App\Models\BlogPost;
use function Livewire\Volt\layout;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;

layout('components.marketing-layout', ['title' => 'Blog']);

state(['post' => null]);

mount(function (string $slug) {
    $post = BlogPost::query()->published()->where('slug', $slug)->first();
    if (! $post) {
        abort(404);
    }
    $this->post = $post;
});

?>

<div class="max-w-3xl mx-auto py-16 px-6 lg:px-8">
  <a href="{{ route('blog.index') }}" class="text-sm text-kipit-primary hover:underline">← Back to blog</a>

  <article class="mt-6">
    <p class="text-sm text-gray-500 mb-3">
      {{ optional($post->published_at)->format('M j, Y') }}
      @if($post->author)
        · {{ $post->author->name }}
      @endif
    </p>
    <h1 class="text-4xl font-extrabold text-gray-900 mb-6">{{ $post->title }}</h1>

    @if($post->cover_image)
      <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('filesystems.upload_disk', 'public'))->url($post->cover_image) }}" alt="" class="w-full rounded-xl mb-8 object-cover max-h-96">
    @endif

    <div class="prose prose-lg max-w-none text-gray-800">
      {!! $post->body !!}
    </div>
  </article>
</div>
