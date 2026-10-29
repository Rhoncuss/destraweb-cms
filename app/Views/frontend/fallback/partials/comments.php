<?php
/**
 * @var list<array<string, mixed>> $comments
 * @var array<string, mixed>|null  $commentUser
 * @var bool                       $commentsOpen
 * @var bool                       $commentRequiresLogin
 * @var bool                       $commentGuestsAllowed
 * @var int                        $commentMaxLength
 * @var string                     $commentCaptcha
 * @var array<string, mixed>       $content
 */
$comments  = $comments ?? [];
$count     = (int) ($commentCount ?? 0);
$maxLength = (int) ($commentMaxLength ?? 3000);
$open      = ! empty($commentsOpen);
$user      = $commentUser ?? null;
$guestOk   = ! empty($commentGuestsAllowed) || $user !== null;
$formReady = $open && $guestOk;
?>
<section class="dst-comments" id="comments">
    <header class="dst-comments__head">
        <h2 class="dst-section-title"><?= esc(ui('comments', 'Comments')) ?> <span class="dst-comments__count"><?= $count ?></span></h2>
    </header>

    <?php if (! $open): ?>
        <p class="dst-note"><?= esc(ui('comments_closed', 'Comments are closed for this story.')) ?></p>
    <?php elseif (! $formReady): ?>
        <div class="dst-note dst-note--action">
            <p><?= esc(ui('comments_login', 'Join the conversation — sign in to leave a comment.')) ?></p>
            <a class="dst-btn dst-btn--primary" href="<?= esc(site_url('login')) ?>"><?= esc(ui('sign_in', 'Sign in')) ?></a>
        </div>
    <?php endif; ?>

    <?php if ($comments !== []): ?>
        <ol class="dst-comment-list">
            <?php
            $renderComment = function (array $comment, int $depth = 0) use (&$renderComment): void {
                ?>
                <li class="dst-comment" id="comment-<?= (int) $comment['id'] ?>" data-depth="<?= $depth ?>">
                    <div class="dst-comment__avatar" aria-hidden="true"><?= esc(initials((string) $comment['author_name'])) ?></div>
                    <div class="dst-comment__body">
                        <div class="dst-comment__meta">
                            <strong><?= esc((string) $comment['author_name']) ?></strong>
                            <time datetime="<?= esc((string) $comment['created_at']) ?>"><?= esc(human_date((string) $comment['created_at'])) ?></time>
                        </div>
                        <div class="dst-comment__text"><?= nl2br(esc((string) $comment['body'])) ?></div>
                        <?php if ($depth < 3): ?>
                            <button type="button" class="dst-comment__reply" data-reply="<?= (int) $comment['id'] ?>">
                                <i class="fa-solid fa-reply" aria-hidden="true"></i> <?= esc(ui('reply', 'Reply')) ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php if (! empty($comment['children'])): ?>
                        <ol class="dst-comment-list dst-comment-list--nested">
                            <?php foreach ($comment['children'] as $child): ?>
                                <?php $renderComment($child, $depth + 1); ?>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </li>
                <?php
            };

            foreach ($comments as $comment) {
                $renderComment($comment);
            }
            ?>
        </ol>
    <?php elseif ($open): ?>
        <p class="dst-note"><?= esc(ui('comments_first', 'No comments yet — be the first to share your thoughts.')) ?></p>
    <?php endif; ?>

    <?php if ($formReady): ?>
        <form class="dst-comment-form" id="comment-form" data-comment-form data-content-id="<?= (int) $content['id'] ?>" novalidate>
            <h3 class="dst-comment-form__title" data-comment-title><?= esc(ui('leave_comment', 'Leave a comment')) ?></h3>
            <input type="hidden" name="content_id" value="<?= (int) $content['id'] ?>">
            <input type="hidden" name="parent_id" value="0" data-parent-id>
            <input type="hidden" name="locale" value="<?= esc(current_locale()) ?>">
            <input type="text" name="website" class="dst-hp" tabindex="-1" autocomplete="off" aria-hidden="true">

            <?php if ($user === null): ?>
                <div class="dst-field-row">
                    <div class="dst-field">
                        <label for="comment-name"><?= esc(ui('name', 'Name')) ?></label>
                        <input id="comment-name" type="text" name="name" required maxlength="120" autocomplete="name">
                    </div>
                    <div class="dst-field">
                        <label for="comment-email"><?= esc(ui('email', 'Email')) ?></label>
                        <input id="comment-email" type="email" name="email" required maxlength="190" autocomplete="email">
                        <small><?= esc(ui('email_private', 'Your email is never published.')) ?></small>
                    </div>
                </div>
            <?php else: ?>
                <p class="dst-comment-form__as">
                    <?= esc(ui('commenting_as', 'Commenting as')) ?> <strong><?= esc((string) $user['name']) ?></strong>
                </p>
            <?php endif; ?>

            <div class="dst-field">
                <label class="dst-sr" for="comment-body"><?= esc(ui('comment', 'Comment')) ?></label>
                <textarea id="comment-body" name="body" rows="5" required maxlength="<?= $maxLength ?>"
                          placeholder="<?= esc(ui('comment_placeholder', 'Share your thoughts…')) ?>"></textarea>
                <small class="dst-counter" data-comment-counter>0 / <?= $maxLength ?></small>
            </div>

            <p class="dst-comment-form__replyto" data-reply-note hidden>
                <span data-reply-label><?= esc(ui('replying_to', 'Replying to a comment')) ?></span>
                <button type="button" data-cancel-reply><?= esc(ui('cancel', 'Cancel')) ?></button>
            </p>

            <?= $commentCaptcha ?? '' ?>

            <div class="dst-comment-form__actions">
                <button type="submit" class="dst-btn dst-btn--primary">
                    <span><?= esc(ui('post_comment', 'Post comment')) ?></span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>
                <p class="dst-comment-form__msg" data-comment-msg role="status" aria-live="polite"></p>
            </div>
        </form>
    <?php endif; ?>
</section>
