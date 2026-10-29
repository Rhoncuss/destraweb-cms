<?php
/**
 * @var array<string, mixed> $content
 * @var array<string, mixed>|null $form
 */
$content = $content ?? [];
$form    = $form ?? null;
$image   = (string) ($content['image_url'] ?? '');
$updated = (string) ($content['updated_at'] ?? '');
?>
<div class="dst-container dst-page">
    <?= view('frontend/fallback/partials/breadcrumbs', ['crumbs' => $crumbs ?? []]) ?>

    <header class="dst-page__head" data-reveal>
        <span class="dst-kicker"><?= esc(ui('page', 'Page')) ?></span>
        <h1 class="dst-display"><?= esc((string) $content['title']) ?></h1>
        <?php if (! empty($content['excerpt'])): ?>
            <p class="dst-lede"><?= esc((string) $content['excerpt']) ?></p>
        <?php endif; ?>
    </header>

    <?php if ($image !== ''): ?>
        <figure class="dst-article-cover dst-article-cover--page" data-reveal>
            <img src="<?= esc($image) ?>" alt="<?= esc((string) $content['title']) ?>" loading="eager" decoding="async">
        </figure>
    <?php endif; ?>

    <div class="dst-page__body">
        <div class="dst-prose dst-prose--page" data-article-body>
            <?= render_content((string) $content['body']) ?>
        </div>

        <?php if (! empty($orderForm)): ?>
            <section class="dst-page__form" id="order">
                <?= view('frontend/fallback/partials/order-form', ['content' => $content]) ?>
            </section>
        <?php endif; ?>

        <?php if ($form !== null): ?>
            <section class="dst-page__form" id="contact">
                <div class="dst-page__form-head">
                    <span class="dst-kicker"><?= esc(ui('get_in_touch', 'Get in touch')) ?></span>
                    <h2><?= esc((string) $form['name']) ?></h2>
                    <?php if (! empty($form['description'])): ?>
                        <p><?= esc((string) $form['description']) ?></p>
                    <?php endif; ?>
                </div>
                <?= view('frontend/fallback/partials/form', ['form' => $form]) ?>
            </section>
        <?php endif; ?>

        <?php if (! empty($share)): ?>
            <div class="dst-page__share">
                <?= view('frontend/fallback/partials/share', ['share' => $share]) ?>
            </div>
        <?php endif; ?>

        <?php if ($updated !== ''): ?>
            <p class="dst-page__updated"><i class="fa-regular fa-clock" aria-hidden="true"></i> <?= esc(ui('last_updated', 'Last updated')) ?> <?= esc(human_date($updated)) ?></p>
        <?php endif; ?>
    </div>
</div>
