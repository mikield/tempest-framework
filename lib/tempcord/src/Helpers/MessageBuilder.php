<?php

namespace Tempcord\Helpers;

use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\Components\ChannelSelect;
use Discord\Builders\Components\MentionableSelect;
use Discord\Builders\Components\Option;
use Discord\Builders\Components\RoleSelect;
use Discord\Builders\Components\StringSelect;
use Discord\Builders\Components\UserSelect;
use Discord\Discord;
use Discord\Parts\Channel\Message as ChannelMessage;
use Discord\Parts\Channel\Poll\Poll;
use Discord\Parts\Guild\Sticker;
use Discord\Parts\Interactions\Interaction;
use Exception;
use React\Promise\PromiseInterface;
use RuntimeException;
use Tempcord\Tempcord;
use Throwable;
use function Tempest\Support\Arr\chunk;
use function Tempest\Support\Arr\filter;
use function Tempest\Support\Arr\map_iterable;
use function Tempest\Support\str;

final class MessageBuilder
{
    /**
     * The message username.
     */
    private string $username;

    /**
     * The message body.
     */
    private string $body = '';

    /**
     * The message avatar URL.
     */
    private ?string $avatarUrl = null;

    /**
     * The text to speech state.
     */
    private bool $tts = false;

    /**
     * The message title.
     */
    private ?string $title = null;

    /**
     * The message content.
     */
    private ?string $content = null;

    /**
     * The message color.
     */
    private ?string $color = null;

    /**
     * The message footer icon.
     */
    private ?string $footerIcon = null;

    /**
     * The message footer text.
     */
    private ?string $footerText = null;

    /**
     * The message thumbnail URL.
     */
    private ?string $thumbnailUrl = null;

    /**
     * The message URL.
     */
    private ?string $url = null;

    /**
     * The message image URL.
     */
    private ?string $imageUrl = null;

    /**
     * The message timestamp.
     */
    private ?string $timestamp = null;

    /**
     * The message author name.
     */
    private ?string $authorName = null;

    /**
     * The message author URL.
     */
    private ?string $authorUrl = null;

    /**
     * The message author icon.
     */
    private ?string $authorIcon = null;

    /**
     * The message fields.
     */
    private array $fields = [];

    /**
     * The message components.
     */
    private array $components = [] {
        get {
            return $this->components;
        }
    }

    /**
     * The message buttons.
     */
    private array $buttons = [];

    /**
     * The message select menus.
     */
    private array $selects = [];

    /**
     * The message files.
     */
    private array $files = [];

    /**
     * The message poll.
     */
    private ?Poll $poll = null;


    /**
     * The message stickers.
     */
    private array $stickers = [];

    /**
     * The additional message embeds.
     */
    private array $embeds = [];

    /**
     * The default embed colors.
     */
    private array $colors = [
        'default' => 3066993,
        'success' => 3066993,
        'error' => 15158332,
        'warning' => 15105570,
        'info' => 3447003,
    ];

    /**
     * The interaction route prefix.
     */
    private ?string $routePrefix = null {
        get {
            return $this->routePrefix;
        }
    }

    private Discord $discord;

    /**
     * Create a new Discord message instance.
     *
     * @return void
     */
    public function __construct(Tempcord $tempcord)
    {
        $this->discord = $tempcord->discord;

        $this
            ->username($username = $tempcord->discord->user->username)
            ->avatar($avatar = $tempcord->discord->user->avatar)
            ->authorName($username)
            ->authorIcon($avatar)
            ->success();
    }

    /**
     * Build the message.
     */
    public function build(): \Discord\Builders\MessageBuilder
    {
        $message = \Discord\Builders\MessageBuilder::new()
            ->setUsername($this->username)
            ->setAvatarUrl($this->avatarUrl)
            ->setTts($this->tts)
            ->setContent($this->body)
            ->setStickers($this->stickers)
            ->setComponents($this->components);

        if ($this->hasContent() || $this->hasFields()) {
            $message->addEmbed($this->getEmbed());
        }

        if ($this->hasEmbeds()) {
            foreach ($this->embeds as $embed) {
                $message->addEmbed($embed);
            }
        }

        if ($this->hasSelects()) {
            foreach ($this->selects as $select) {
                $message->addComponent($select);
            }
        }

        if ($this->hasButtons()) {
            foreach ($this->getButtons() as $button) {
                $message->addComponent($button);
            }
        }

        if ($this->hasPoll()) {
            $message->setPoll($this->poll);
        }

        if ($this->hasFiles()) {
            foreach ($this->files as $file) {
                $message->addFileFromContent($file['filename'], $file['content']);
            }
        }

        return $message;
    }


    /**
     * Reply to a message or interaction.
     */
    public function reply(Interaction|ChannelMessage $message, bool $ephemeral = false): PromiseInterface
    {
        if ($message instanceof Interaction) {
            return $message->respondWithMessage($this->build(), ephemeral: $ephemeral);
        }

        return $message->reply($this->build());
    }

    /**
     * Edit an existing message or interaction message.
     */
    public function edit(Interaction|ChannelMessage $message): PromiseInterface
    {
        if ($message instanceof Interaction) {
            return $message->updateMessage($this->build());
        }

        return $message->edit($this->build());
    }

    /**
     * Edit an existing message if it is owned by the bot, otherwise replying instead.
     */
    public function editOrReply(Interaction|ChannelMessage $message, bool $ephemeral = false): PromiseInterface
    {
        if ($message instanceof Interaction) {
            return $message->message?->user_id === $this->discord->id
                ? $this->edit($message)
                : $this->reply($message, $ephemeral);
        }

        return $message->author->id === $this->discord->id
            ? $this->edit($message)
            : $this->reply($message);
    }

    /**
     * Get the embed.
     */
    public function getEmbed(): array
    {
        return filter([
            'title' => $this->title,
            'description' => $this->content,
            'url' => $this->url,
            'timestamp' => $this->timestamp,
            'color' => $this->color,
            'footer' => [
                'text' => $this->footerText,
                'icon_url' => $this->footerIcon,
            ],
            'thumbnail' => [
                'url' => $this->thumbnailUrl,
            ],
            'image' => [
                'url' => $this->imageUrl,
            ],
            'author' => [
                'name' => $this->authorName,
                'url' => $this->authorUrl,
                'icon_url' => $this->authorIcon,
            ],
            'fields' => $this->fields,
        ]);
    }

    /**
     * Get the button components.
     */
    public function getButtons(): array
    {
        if (!$this->hasButtons()) {
            return [];
        }

        return map_iterable(chunk($this->buttons, 5), function ($buttons) {
            $row = ActionRow::new();

            foreach ($buttons as $button) {
                $row->addComponent($button);
            }

            return $row;
        });
    }

    /**
     * Set the message username.
     */
    public function username(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set the message content.
     */
    public function content(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Determine if the message has content.
     */
    public function hasContent(): bool
    {
        return !empty($this->content);
    }

    /**
     * Set the message avatar.
     */
    public function avatar(?string $avatarUrl): self
    {
        return $this->avatarUrl($avatarUrl);
    }

    /**
     * Set the message avatar URL.
     */
    public function avatarUrl(?string $avatarUrl): self
    {
        $this->avatarUrl = $avatarUrl;

        return $this;
    }

    /**
     * Set whether the message should be text-to-speech.
     */
    public function tts(bool $tts): self
    {
        $this->tts = $tts;

        return $this;
    }

    /**
     * Set the message title.
     */
    public function title(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the message body.
     */
    public function body(string $body = ''): self
    {
        $this->body = $body;

        return $this;
    }


    /**
     * Determine if the message has files.
     */
    public function hasFiles(): bool
    {
        return !empty($this->files);
    }


    /**
     * Set the message color.
     */
    public function color(string $color): self
    {
        $hexColor = match ($color) {
            'success' => $this->colors['success'],
            'error' => $this->colors['error'],
            'warning' => $this->colors['warning'],
            'info' => $this->colors['info'],
            default => $color,
        };

        if (str_starts_with($hexColor, '#')) {
            $color = hexdec(
                str($color)->replace('#', '')->toString()
            );
        }

        $this->color = (string)$hexColor;

        return $this;
    }

    /**
     * Set the message color to success.
     */
    public function success(): self
    {
        return $this->color('success');
    }

    /**
     * Set the message color to error.
     */
    public function error(): self
    {
        return $this->color('error');
    }

    /**
     * Set the message color to warning.
     */
    public function warning(): self
    {
        return $this->color('warning');
    }

    /**
     * Set the message color to info.
     */
    public function info(): self
    {
        return $this->color('info');
    }

    /**
     * Set the message footer icon.
     */
    public function footerIcon(?string $footerIcon): self
    {
        $this->footerIcon = $footerIcon;

        return $this;
    }

    /**
     * Set the message footer text.
     */
    public function footerText(?string $footerText): self
    {
        $this->footerText = $footerText;

        return $this;
    }

    /**
     * Set the message thumbnail URL.
     */
    public function thumbnail(?string $thumbnailUrl): self
    {
        return $this->thumbnailUrl($thumbnailUrl);
    }

    /**
     * Set the message thumbnail URL.
     */
    public function thumbnailUrl(?string $thumbnailUrl): self
    {
        $this->thumbnailUrl = $thumbnailUrl;

        return $this;
    }

    /**
     * Set the message URL.
     */
    public function url(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Set the message image URL.
     */
    public function image(?string $imageUrl): self
    {
        return $this->imageUrl($imageUrl);
    }

    /**
     * Set the message image URL.
     */
    public function imageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    /**
     * Add a sticker to the message.
     */
    public function sticker(string|Sticker $sticker): self
    {
        $this->stickers[] = $sticker instanceof Sticker
            ? $sticker->id
            : $sticker;

        return $this;
    }

    /**
     * Add stickers to the message.
     */
    public function stickers(array $stickers): self
    {
        foreach ($stickers as $sticker) {
            $this->sticker($sticker);
        }

        return $this;
    }

    /**
     * Clear the stickers from the message.
     */
    public function clearStickers(): self
    {
        $this->stickers = [];

        return $this;
    }

    /**
     * Set the message author name.
     */
    public function authorName(?string $authorName): self
    {
        $this->authorName = $authorName;

        return $this;
    }

    /**
     * Set the message author URL.
     */
    public function authorUrl(?string $authorUrl): self
    {
        $this->authorUrl = $authorUrl;

        return $this;
    }

    /**
     * Set the message author icon.
     */
    public function authorIcon(?string $authorIcon): self
    {
        $this->authorIcon = $authorIcon;

        return $this;
    }

    /**
     * Clear the message author.
     */
    public function clearAuthor(): self
    {
        return $this->authorName('')->authorIcon('');
    }

    /**
     * Set the message fields.
     */
    public function fields(array $fields, bool $inline = true): self
    {
        foreach ($fields as $key => $value) {
            $this->field($key, $value, $inline);
        }

        return $this;
    }

    /**
     * Add a field to the message.
     */
    public function field(string $name, mixed $value, bool $inline = true, bool $condition = false): self
    {
        if ($condition) {
            return $this;
        }

        $this->fields[] = [
            'name' => $name,
            'value' => (string)$value,
            'inline' => $inline,
        ];

        return $this;
    }

    /**
     * Add a code field to the message.
     */
    public function codeField(string $name, string $value, string $language = 'php', bool $condition = false): self
    {
        if ($condition) {
            return $this;
        }

        return $this->field($name, "```{$language}\n{$value}\n```", inline: false);
    }

    /**
     * Clear the fields from the message.
     */
    public function clearFields(): self
    {
        $this->fields = [];

        return $this;
    }

    /**
     * Determine if the message has fields.
     */
    public function hasFields(): bool
    {
        return !empty($this->fields);
    }

    /**
     * Set the message components.
     */
    public function components(array $components): self
    {
        $this->components = $components;

        return $this;
    }

    /**
     * Add a select menu to the message.
     */
    public function select(
        array     $items = [],
        ?callable $listener = null,
        ?string   $placeholder = null,
        ?string   $id = null,
        bool      $disabled = false,
        bool      $hidden = false,
        int       $minValues = 1,
        int       $maxValues = 1,
        ?string   $type = null,
        ?string   $route = null,
        ?array    $options = []
    ): self
    {
        if ($hidden) {
            return $this;
        }

        $select = match ($type) {
            'channel' => ChannelSelect::new(),
            'mentionable' => MentionableSelect::new(),
            'role' => RoleSelect::new(),
            'user' => UserSelect::new(),
            default => StringSelect::new(),
        };

        $select = $select
            ->setPlaceholder($placeholder)
            ->setMinValues($minValues)
            ->setMaxValues($maxValues)
            ->setDisabled($disabled);

        if ($id) {
            $select = $select->setCustomId($id);
        }

        if ($route) {
            $select = $this->routePrefix
                ? $select->setCustomId("{$this->routePrefix}@{$route}")
                : $select->setCustomId($route);
        }

        if ($listener) {
            $select = $select->setListener($listener, $this->discord);
        }

        if ($options) {
            foreach ($options as $key => $option) {
                $key = str($key)->camel()->upperFirst()->start('set')->toString();

                try {
                    $select = $select->{$key}($option);
                } catch (Throwable) {

                    continue;
                }
            }
        }

        foreach ($items as $key => $value) {
            if (!is_array($value)) {
                $select->addOption(
                    Option::new(is_int($key) ? $value : $key, $value)
                );

                continue;
            }

            $option = Option::new($value['label'] ?? $key, $value['value'] ?? $key)
                ->setDescription($value['description'] ?? null)
                ->setEmoji($value['emoji'] ?? null)
                ->setDefault($value['default'] ?? false);

            $select->addOption($option);
        }

        $this->selects[] = $select;

        return $this;
    }

    /**
     * Clear the select menus from the message.
     */
    public function clearSelects(): self
    {
        $this->selects = [];

        return $this;
    }

    /**
     * Determine if the message has select menus.
     */
    public function hasSelects(): bool
    {
        return !empty($this->selects);
    }

    /**
     * Add a button to the message.
     */
    public function button(
        string  $label,
        mixed   $value = null,
        mixed   $emoji = null,
        ?int    $style = null,
        bool    $disabled = false,
        bool    $hidden = false,
        ?string $id = null,
        ?string $route = null,
        array   $options = []
    ): self
    {
        if ($hidden) {
            return $this;
        }

        $style = $style ?? (is_string($value) ? Button::STYLE_LINK : Button::STYLE_PRIMARY);

        $button = Button::new($style)
            ->setLabel($label)
            ->setEmoji($emoji)
            ->setDisabled($disabled);

        if ($id) {
            $button = $button->setCustomId($id);
        }

        if ($route) {
            $button = $this->routePrefix
                ? $button->setCustomId("{$this->routePrefix}@{$route}")
                : $button->setCustomId($route);
        }

        if ($options) {
            foreach ($options as $key => $option) {
                $key = str($key)->camel()->upperFirst()->start('set')->toString();

                try {
                    $button = $button->{$key}($option);
                } catch (Throwable) {

                    continue;
                }
            }
        }

        $button = match ($style) {
            Button::STYLE_LINK => $button->setUrl($value),
            default => $value ? $button->setListener($value, $this->discord) : $button,
        };

        if (!$value && !$route && !$id) {
            throw new \RuntimeException('Message buttons must contain a valid `value`, `route`, or `id`.');
        }

        $this->buttons[] = $button;

        return $this;
    }

    /**
     * Add buttons to the message.
     */
    public function buttons(array $buttons): self
    {
        foreach ($buttons as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $value = [$key, $value];
            }

            $this->button(...$value);
        }

        return $this;
    }

    /**
     * Clear the buttons from the message.
     */
    public function clearButtons(): self
    {
        $this->buttons = [];

        return $this;
    }

    /**
     * Determine if the message has buttons.
     */
    public function hasButtons(): bool
    {
        return !empty($this->buttons);
    }

    /**
     * Add a poll to the message.
     */
    public function poll(string $question, array $answers, int $duration = 24, bool $multiselect = false): self
    {
        $answers = map_iterable($answers, fn($value, $key) => is_string($key)
            ? ['emoji' => $key, 'text' => $value]
            : ['text' => $value]);

        $this->poll = new Poll($this->discord)
            ->setQuestion($question)
            ->setAnswers($answers)
            ->setDuration($duration)
            ->setAllowMultiselect($multiselect);

        return $this;
    }

    /**
     * Clear the poll from the message.
     */
    public function clearPoll(): self
    {
        $this->poll = null;

        return $this;
    }

    /**
     * Determine if the message has a poll.
     */
    public function hasPoll(): bool
    {
        return !is_null($this->poll);
    }

    /**
     * Add an embed to the message.
     */
    public function withEmbed(\Discord\Builders\MessageBuilder|self $builder): self
    {
        if (count($this->embeds) === 10) {
            throw new RuntimeException('Messages cannot exceed 10 embeds.');
        }

        if ($builder instanceof self) {
            $builder = $builder->build();
        }

        $embeds = $builder->getEmbeds();

        if (!$embeds) {
            throw new Exception('Builder must contain at least one embed.');
        }

        $this->embeds[] = $embeds[0];

        return $this;
    }

    /**
     * Add additional embeds to the message.
     * @throws Exception
     */
    public function withEmbeds(array $builders): self
    {
        foreach ($builders as $builder) {
            $this->withEmbed($builder);
        }

        return $this;
    }

    /**
     * Determine if the message has additional embeds.
     */
    public function hasEmbeds(): bool
    {
        return !empty($this->embeds);
    }

    /**
     * Clear the additional embeds from the message.
     */
    public function clearEmbeds(): self
    {
        $this->embeds = [];

        return $this;
    }

    /**
     * Set the interaction route prefix.
     */
    public function routePrefix(?string $routePrefix): self
    {
        $this->routePrefix = str($routePrefix)->slug();

        return $this;
    }

}
