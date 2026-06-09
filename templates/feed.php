<?php
/**
 * Frontend template for the social feed grid.
 *
 * Expects the following variables in scope (provided by the renderer):
 *
 * @var array  $posts   List of normalised post arrays.
 * @var string $error   Error message, or empty string.
 * @var string $network Network key.
 * @var int    $columns Number of grid columns.
 * @var string $title   Optional heading.
 *
 * IMPORTANT: every dynamic value is escaped here, at output time.
 *
 * @package SocialFeed
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="social-feed social-feed--<?php echo esc_attr( $network ); ?>">

	<?php if ( '' !== $title ) : ?>
		<h2 class="social-feed__title"><?php echo esc_html( $title ); ?></h2>
	<?php endif; ?>

	<?php if ( '' !== $error ) : ?>
		<p class="social-feed__error"><?php echo esc_html( $error ); ?></p>
	<?php endif; ?>

	<?php if ( empty( $posts ) && '' === $error ) : ?>
		<p class="social-feed__empty"><?php esc_html_e( 'No posts to display yet.', 'social-feed' ); ?></p>
	<?php endif; ?>

	<?php if ( ! empty( $posts ) ) : ?>
		<ul class="social-feed__grid" style="--social-feed-columns: <?php echo esc_attr( $columns ); ?>;">
			<?php foreach ( $posts as $post ) : ?>
				<li class="social-feed__item">
					<a
						class="social-feed__link"
						href="<?php echo esc_url( $post['permalink'] ); ?>"
						target="_blank"
						rel="noopener noreferrer"
					>
						<?php if ( '' !== $post['image'] ) : ?>
							<div class="social-feed__media">
								<img
									class="social-feed__image"
									src="<?php echo esc_url( $post['image'] ); ?>"
									alt="<?php echo esc_attr( wp_trim_words( $post['text'], 12, '' ) ); ?>"
									loading="lazy"
								/>
							</div>
						<?php endif; ?>

						<div class="social-feed__body">
							<?php if ( '' !== $post['text'] ) : ?>
								<p class="social-feed__text">
									<?php echo esc_html( wp_trim_words( $post['text'], 24 ) ); ?>
								</p>
							<?php endif; ?>

							<footer class="social-feed__meta">
								<?php if ( '' !== $post['author'] ) : ?>
									<span class="social-feed__author"><?php echo esc_html( $post['author'] ); ?></span>
								<?php endif; ?>
								<span class="social-feed__cta"><?php esc_html_e( 'View post', 'social-feed' ); ?></span>
							</footer>
						</div>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

</section>
