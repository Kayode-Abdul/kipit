<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostResource\Pages;
use App\Models\Admin;
use App\Models\BlogPost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = 'Blog Posts';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        /** @var Admin|null $admin */
        $admin = Auth::guard('admin')->user();

        return $admin?->canManageBlog() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
            Forms\Components\TextInput::make('slug')
                ->required()
                ->unique(ignoreRecord: true),
            Forms\Components\Textarea::make('excerpt')
                ->rows(3)
                ->maxLength(500)
                ->columnSpanFull(),
            Forms\Components\RichEditor::make('body')
                ->required()
                ->columnSpanFull(),
            Forms\Components\FileUpload::make('cover_image')
                ->image()
                ->directory('blog')
                ->disk(config('filesystems.upload_disk', 'public'))
                ->nullable(),
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                ])
                ->required()
                ->default('draft'),
            Forms\Components\DateTimePicker::make('published_at')
                ->nullable(),
            Forms\Components\TextInput::make('seo_title')->nullable(),
            Forms\Components\Textarea::make('seo_description')->rows(2)->maxLength(300)->nullable(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable()->limit(50),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => $state === 'published' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('author.name')->label('Author'),
                Tables\Columns\TextColumn::make('published_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('viewPublic')
                    ->label('View')
                    ->url(fn (BlogPost $record): string => route('blog.show', $record->slug))
                    ->openUrlInNewTab()
                    ->visible(fn (BlogPost $record): bool => $record->isPublished()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit' => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}
