<?php

return [
	'breakpoints' => ['desktop', 'tablet', 'mobile'],

	'permissions' => [
		'page-builder.page.viewAny',
		'page-builder.page.view',
		'page-builder.page.create',
		'page-builder.page.update',
		'page-builder.page.delete',
		'page-builder.page.publish',
		'page-builder.page.revision.view',
		'page-builder.page.revision.rollback',
		'page-builder.library.manage',
	],

	'internal_blocks' => [
		[
			'key' => 'heading',
			'label' => 'Heading',
			'icon' => '🔠',
			'category' => 'Typography',
			'description' => 'Section heading with supporting paragraph.',
			'html_template' => '<section class="pbx-section"><h2 class="pbx-heading">Build beautiful pages faster</h2><p class="pbx-text">Use this heading block to introduce your section with a clear message.</p></section>',
		],
		[
			'key' => 'paragraph',
			'label' => 'Paragraph',
			'icon' => '📝',
			'category' => 'Typography',
			'description' => 'Readable paragraph block for article-like content.',
			'html_template' => '<section class="pbx-section"><p class="pbx-text">Create rich, readable content with clean spacing and typography. This paragraph block is optimized for desktop, tablet, and mobile.</p></section>',
		],
		[
			'key' => 'button',
			'label' => 'Button',
			'icon' => '🔘',
			'category' => 'Actions',
			'description' => 'Primary call-to-action button.',
			'html_template' => '<section class="pbx-section"><a class="pbx-btn" href="#">Get started</a></section>',
		],
		[
			'key' => 'image',
			'label' => 'Image',
			'icon' => '🖼️',
			'category' => 'Media',
			'description' => 'Responsive image with optional caption.',
			'html_template' => '<figure class="pbx-section"><img class="pbx-image" src="https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1400&q=80" alt="Modern workspace" /><figcaption class="pbx-caption">Add a short caption to explain the visual.</figcaption></figure>',
		],
		[
			'key' => 'columns-2',
			'label' => '2 Columns',
			'icon' => '🧱',
			'category' => 'Layout',
			'description' => 'Two responsive columns that stack on mobile.',
			'html_template' => '<section class="pbx-section pbx-columns pbx-columns-2"><div class="pbx-card"><h3 class="pbx-subheading">Left column</h3><p class="pbx-text">Highlight feature, value, or supporting details here.</p></div><div class="pbx-card"><h3 class="pbx-subheading">Right column</h3><p class="pbx-text">Use the second column for comparison or another message.</p></div></section>',
		],
		[
			'key' => 'columns-3',
			'label' => '3 Columns',
			'icon' => '🧩',
			'category' => 'Layout',
			'description' => 'Three-column responsive layout for summaries.',
			'html_template' => '<section class="pbx-section pbx-columns pbx-columns-3"><div class="pbx-card"><h3 class="pbx-subheading">Column 1</h3><p class="pbx-text">Short supporting content.</p></div><div class="pbx-card"><h3 class="pbx-subheading">Column 2</h3><p class="pbx-text">Short supporting content.</p></div><div class="pbx-card"><h3 class="pbx-subheading">Column 3</h3><p class="pbx-text">Short supporting content.</p></div></section>',
		],
		[
			'key' => 'video-embed',
			'label' => 'Video',
			'icon' => '🎬',
			'category' => 'Media',
			'description' => 'Responsive 16:9 iframe video embed block.',
			'html_template' => '<section class="pbx-section"><div class="pbx-video"><iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="Video" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div></section>',
		],
		[
			'key' => 'link-card',
			'label' => 'Link Card',
			'icon' => '🔗',
			'category' => 'Actions',
			'description' => 'Clickable card for navigation and highlights.',
			'html_template' => '<section class="pbx-section"><a href="#" class="pbx-link-card"><span class="pbx-link-card__eyebrow">Resource</span><span class="pbx-link-card__title">Read full documentation</span><span class="pbx-link-card__desc">Guide users to important content with one elegant card.</span></a></section>',
		],
		[
			'key' => 'card-grid',
			'label' => 'Card Grid',
			'icon' => '🔲',
			'category' => 'Layout',
			'description' => 'Responsive grid for products, services, or features.',
			'html_template' => '<section class="pbx-section pbx-grid"><article class="pbx-card"><h3 class="pbx-subheading">Starter</h3><p class="pbx-text">Great for small projects.</p></article><article class="pbx-card"><h3 class="pbx-subheading">Pro</h3><p class="pbx-text">For growing teams and traffic.</p></article><article class="pbx-card"><h3 class="pbx-subheading">Enterprise</h3><p class="pbx-text">Advanced controls and scale.</p></article><article class="pbx-card"><h3 class="pbx-subheading">Custom</h3><p class="pbx-text">Tailored implementation support.</p></article></section>',
		],
		[
			'key' => 'hero-banner',
			'label' => 'Hero Banner',
			'icon' => '✨',
			'category' => 'Sections',
			'description' => 'Top-of-page hero with heading, text, and CTA.',
			'html_template' => '<section class="pbx-hero"><div><p class="pbx-eyebrow">Modern Page Builder</p><h1 class="pbx-hero__title">Design responsive experiences without code lock-in</h1><p class="pbx-hero__text">Everything remains plain HTML/CSS, so your team keeps full control over presentation and structure.</p><a href="#" class="pbx-btn">Start building</a></div></section>',
		],
		[
			'key' => 'stats-row',
			'label' => 'Stats Row',
			'icon' => '📊',
			'category' => 'Data',
			'description' => 'Key metrics displayed in a clean responsive row.',
			'html_template' => '<section class="pbx-section pbx-stats"><div class="pbx-stat"><strong>120K+</strong><span>Monthly visitors</span></div><div class="pbx-stat"><strong>4.9/5</strong><span>User rating</span></div><div class="pbx-stat"><strong>99.95%</strong><span>Uptime SLA</span></div></section>',
		],
		[
			'key' => 'testimonial',
			'label' => 'Testimonial',
			'icon' => '💬',
			'category' => 'Social Proof',
			'description' => 'Customer quote with author and role.',
			'html_template' => '<section class="pbx-section"><blockquote class="pbx-quote">“Pagify helped us cut page build time in half while keeping full design freedom.”<cite><span>Lan Nguyen</span><small>Product Lead, Nova Team</small></cite></blockquote></section>',
		],
		[
			'key' => 'cta-panel',
			'label' => 'CTA Panel',
			'icon' => '🚀',
			'category' => 'Sections',
			'description' => 'High-contrast call-to-action panel.',
			'html_template' => '<section class="pbx-cta"><h2 class="pbx-cta__title">Ready to launch your next page?</h2><p class="pbx-cta__text">Use this panel near the bottom to drive your primary conversion action.</p><a class="pbx-btn pbx-btn--light" href="#">Book a demo</a></section>',
		],
		[
			'key' => 'site-header',
			'label' => 'Header',
			'icon' => '🧭',
			'category' => 'Sections',
			'description' => 'Responsive top navigation header with CTA.',
			'html_template' => '<header class="pbx-header"><div class="pbx-header__inner"><a href="#" class="pbx-header__brand">BrandName</a><nav class="pbx-header__nav"><a href="#">Home</a><a href="#">Features</a><a href="#">Pricing</a><a href="#">Contact</a></nav><a href="#" class="pbx-btn">Get started</a></div></header>',
		],
		[
			'key' => 'site-footer',
			'label' => 'Footer',
			'icon' => '🦶',
			'category' => 'Sections',
			'description' => 'Multi-column footer with links and copyright.',
			'html_template' => '<footer class="pbx-footer"><div class="pbx-footer__grid"><div><h3>BrandName</h3><p>Build faster with complete HTML/CSS freedom.</p></div><div><h4>Product</h4><a href="#">Features</a><a href="#">Pricing</a><a href="#">Integrations</a></div><div><h4>Company</h4><a href="#">About</a><a href="#">Blog</a><a href="#">Careers</a></div><div><h4>Support</h4><a href="#">Help center</a><a href="#">Status</a><a href="#">Contact</a></div></div><div class="pbx-footer__meta">© 2026 BrandName. All rights reserved.</div></footer>',
		],
		[
			'key' => 'pricing-table',
			'label' => 'Pricing',
			'icon' => '💵',
			'category' => 'Commerce',
			'description' => 'Responsive pricing cards with highlighted plan.',
			'html_template' => '<section class="pbx-section pbx-pricing"><article class="pbx-pricing-card"><h3>Starter</h3><p class="pbx-pricing-card__price">$19<span>/mo</span></p><ul><li>1 site</li><li>Basic analytics</li><li>Email support</li></ul><a href="#" class="pbx-btn">Choose starter</a></article><article class="pbx-pricing-card pbx-pricing-card--featured"><span class="pbx-pricing-card__badge">Popular</span><h3>Pro</h3><p class="pbx-pricing-card__price">$49<span>/mo</span></p><ul><li>5 sites</li><li>Advanced analytics</li><li>Priority support</li></ul><a href="#" class="pbx-btn">Choose pro</a></article><article class="pbx-pricing-card"><h3>Scale</h3><p class="pbx-pricing-card__price">$99<span>/mo</span></p><ul><li>Unlimited sites</li><li>Custom reports</li><li>Dedicated success</li></ul><a href="#" class="pbx-btn">Choose scale</a></article></section>',
		],
		[
			'key' => 'faq-list',
			'label' => 'FAQ',
			'icon' => '❓',
			'category' => 'Support',
			'description' => 'Accordion-style FAQ list using semantic details elements.',
			'html_template' => '<section class="pbx-section pbx-faq"><h2 class="pbx-heading">Frequently asked questions</h2><details open><summary>Can I use custom HTML/CSS?</summary><p>Yes. You can edit every block directly and keep full design control.</p></details><details><summary>Does it work on mobile devices?</summary><p>All blocks are responsive by default and adapt to smaller screens.</p></details><details><summary>Can I save reusable sections?</summary><p>Yes, save sections into your library and insert them across pages.</p></details></section>',
		],
		[
			'key' => 'contact-form',
			'label' => 'Contact Form',
			'icon' => '📨',
			'category' => 'Forms',
			'description' => 'Responsive contact form section with modern inputs.',
			'html_template' => '<section class="pbx-section pbx-contact"><div class="pbx-contact__intro"><h2 class="pbx-heading">Let’s talk about your project</h2><p class="pbx-text">Send your requirements and our team will get back within one business day.</p></div><form class="pbx-contact__form"><label>Full name<input type="text" name="name" placeholder="Your name" /></label><label>Email<input type="email" name="email" placeholder="you@example.com" /></label><label>Message<textarea name="message" rows="4" placeholder="How can we help?"></textarea></label><button type="submit" class="pbx-btn">Send message</button></form></section>',
		],
	],

	'default_page_templates' => [
		[
			'slug' => 'landing',
			'name' => 'Landing',
			'category' => 'landing',
			'description' => 'Simple conversion-focused landing layout.',
			'schema_json' => [
				'sections' => [
					[
						'id' => 'hero',
						'blocks' => [
							['type' => 'heading', 'props' => ['text' => 'Landing headline']],
							['type' => 'paragraph', 'props' => ['text' => 'Landing page subtitle']],
							['type' => 'button', 'props' => ['label' => 'Start now', 'href' => '#']],
						],
					],
				],
			],
		],
		[
			'slug' => 'blog',
			'name' => 'Blog',
			'category' => 'blog',
			'description' => 'Header, posts list and CTA.',
			'schema_json' => [
				'sections' => [
					[
						'id' => 'intro',
						'blocks' => [
							['type' => 'heading', 'props' => ['text' => 'Blog title']],
							['type' => 'paragraph', 'props' => ['text' => 'Latest insights and stories.']],
						],
					],
				],
			],
		],
		[
			'slug' => 'docs',
			'name' => 'Docs',
			'category' => 'docs',
			'description' => 'Documentation layout starter.',
			'schema_json' => [
				'sections' => [
					[
						'id' => 'docs-intro',
						'blocks' => [
							['type' => 'heading', 'props' => ['text' => 'Documentation']],
							['type' => 'paragraph', 'props' => ['text' => 'Write guides for your users.']],
						],
					],
				],
			],
		],
		[
			'slug' => 'portfolio',
			'name' => 'Portfolio',
			'category' => 'portfolio',
			'description' => 'Portfolio showcase starter.',
			'schema_json' => [
				'sections' => [
					[
						'id' => 'portfolio-hero',
						'blocks' => [
							['type' => 'heading', 'props' => ['text' => 'Featured work']],
							['type' => 'paragraph', 'props' => ['text' => 'Highlight your best projects.']],
						],
					],
				],
			],
		],
	],
];
