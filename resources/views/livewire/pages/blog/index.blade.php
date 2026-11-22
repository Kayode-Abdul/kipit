<?php

use App\Models\BlogPost;
use function Livewire\Volt\layout;
use function Livewire\Volt\state;

layout('components.marketing-layout', ['title' => 'Blog']);

state([
    'posts' => fn () => BlogPost::query()
        ->published()
        ->latest('published_at')
        ->limit(50)
        ->get(),
]);

?>

<div class="max-w-5xl mx-auto py-16 px-6 lg:px-8">
  <div class="text-center mb-14">
    <h1 class="text-4xl font-extrabold text-gray-900 mb-3">Kipit Blog</h1>
    <p class="text-lg text-gray-600">Product updates, guides, and tips for running your shop.</p>
  </div>

  <div class="space-y-8">
    @forelse($posts as $post)
      <article class="border-b border-gray-200 pb-8">
        <p class="text-sm text-gray-500 mb-2">
          {{ optional($post->published_at)->format('M j, Y') }}
          @if($post->author)
            · {{ $post->author->name }}
          @endif
        </p>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">
          <a href="{{ route('blog.show', $post->slug) }}" class="hover:text-kipit-primary">{{ $post->title }}</a>
        </h2>
        @if($post->excerpt)
          <p class="text-gray-600 mb-4">{{ $post->excerpt }}</p>
        @endif
        <a href="{{ route('blog.show', $post->slug) }}" class="text-kipit-primary font-medium hover:underline">Read more →</a>
      </article>
    @empty
      <p class="text-center text-gray-500">No posts published yet. Check back soon.</p>
    @endforelse
  </div>
</div>
