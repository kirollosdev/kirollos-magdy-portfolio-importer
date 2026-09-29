=== Kirollos Magdy Portfolio Importer ===
Contributors: Kirollos Magdy
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.7.0
License: Proprietary. All rights reserved.

Creates every Kirollos Magdy portfolio project and blog post in one click.

== Description ==

A one-time helper for Kirollos Magdy Portfolio Builder. It writes 14 projects to
the `portfolios` post type, each with:

* Title, excerpt and a body built from the challenge, solution and result copy
* Portfolio Categories, Services and Industries, created if they do not exist
* `project_url`, the live site link the portfolio cards and the Project Link box read
* The descriptive meta (client, industry, services, tech stack, challenge,
  solution, results) for the Dynamic Post Meta widget

It also matches Media Library images to projects by filename: first image becomes
the featured image, any file with "logo" in its name becomes the card logo, the
rest go to a gallery meta.

Metrics and testimonials are deliberately left empty. Those are real figures and
named quotes, and there is no record of genuine ones for these builds.

== Usage ==

1. Activate Kirollos Magdy Portfolio Builder first, so the post type exists.
2. Tools > Portfolio Import.
3. Press "Import everything now".
4. Deactivate and delete this plugin once you are happy with the result.

Safe to run more than once. Existing projects are matched on their live URL and
then their title, and only empty fields are filled, so hand edits survive.

== Blog Importer ==

Tools > Blog Importer publishes the bundled SEO blog posts (29 in this release).

* Posts are created as native Gutenberg blocks, with their category, tags and excerpt.
* Yoast SEO title, meta description and focus keyphrase are filled in.
* A branded 1200x630 cover is set as the featured image, with alt text. A featured
  image you set yourself is never replaced.
* Choose Publish now or Import as drafts. Re-importing updates the existing post
  instead of creating a duplicate, and published posts stay published.

Posts are written as Markdown in modules/blog-importer/source and built with
`node build-posts.js` and `python make-covers.py <fonts-dir>`. The build checks SEO
title and meta description lengths, keyphrase placement and internal links.

== Changelog ==

= 1.0.0 =
* First release. Rebuilt against the portfolios post type and the
  portfolio_category / portfolio_service / portfolio_industry taxonomies,
  replacing the older importer that wrote to the removed `project` post type.

= 1.1.0 =
* Filter terms now come from a short curated vocabulary instead of the prose
  fields, so the sidebar filters group projects instead of listing one each.
* Terms are replaced on a re-run rather than added to, so re-importing tidies
  projects that already carry the old specific terms.
* Added a button to remove portfolio terms left with no projects.

= 1.3.0 =
* Photo import now runs in batches with a live progress bar: how many done, the
  file landing right now, and a running log. Avoids the timeout a forty-file
  import hits when it all runs in the request that uploaded the zip.
* Gallery meta is written as each photo lands, so an interrupted run keeps
  everything it already imported.

= 1.2.0 =
* Imports project screenshots from a zip holding one folder per project.

= 1.4.0 =
* Photo import now replaces what is already on a project instead of adding to
  it, on by default. Only projects present in the zip are touched.
* Optional, off by default: delete the replaced files from the Media Library.

= 1.5.0 =
* Work dates: fill in all fourteen on one screen and save. Writes straight to
  the projects that exist, and the project import fills in the rest.
* Nothing is prefilled; no dates are invented.

= 1.6.0 =
* Photo zips now follow the logo + numbers naming: a file called "logo" becomes
  the featured image and the card logo and stays out of the gallery; the rest
  fill the gallery in number order, capped at four to match the builder.
* Added the folder names used in the current photo set.

= 1.7.0 =
* Merged the Kirollos Blog Importer plugin (1.1.0) in as Tools > Blog Importer. If
  the separate plugin is still active, this copy steps aside and shows a notice;
  deactivate and delete the separate one.
