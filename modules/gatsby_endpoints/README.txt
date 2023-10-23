Gatsby Endpoints allows you to create multiple JSON:API endpoints that can be
used to source content into your Gatsby site with gatsby-source-drupal. The most
common use case is to use this to source content from one Drupal site to
multiple Gatsby websites.

Some example use cases where Gatsby Endpoints can be helpful:

1. You want to create a single CMS but have multiple Gatsby blog websites.
2. You want a single backend with a complex page builder content type (maybe
using paragraphs) and you want to create multiple Gatsby sites that share
common re-usable components.
3. You are a large organization that doesn't want to manage so many Drupal
sites and would like to have multiple Gatsby marketing/product sites that pull
content from a single source. This way your content editors only have one log in
to manage all of your company's content but your developers can use Gatsby!

# Installation & Configuration

1. Install the gatsby_endpoints module on your Drupal website.
2. Create your first Gatsby Endpoint from the Gatsby Endpoint admin page. This
allows you to configure Preview URLs, Build URLs, and allows you to select what
data should be available from the Gatsby Endpoint.
3. Copy the last part of the URL (ex: gatsby/my_endpoint) and past it in your
gatsby-config.js as the `apiBase` config option for `gatsby-source-drupal`.


# IMPORTANT NOTES

You currently need this patch to Drupal core to get the Gatsby Endpoints
Reference Field to work correctly. You do not need this patch if you are not
using a Gatsby Endpoints Reference Field on any of your entity types.

The current patch is for Drupal 9 but seems to apply and work for updated
versions of Drupal 8.

- https://www.drupal.org/project/drupal/issues/3036593#comment-13819628

This module DOES NOT currently work with the gatsby_fastbuilds module. Support
for this module is planned in the future but requires additional development.

This module should be considered under active development. Things might change
in future versions and there are likely undiscovered bugs. Please help us make
it better by reporting issues on Drupal.org or in the #gatsby Drupal Slack
channel.

# Configuration Options

There are some important concepts that Gatsby Endpoints introduces:

## Preview URLs

This option allows you to configure multiple Gatsby Preview servers. This allows
you to use Gatsby Cloud (https://gatsbyjs.com) or your own Gatsby Preview server
to preview your content before it is deployed to your Live site.

Helpful Links:

- https://www.gatsbyjs.com/docs/running-a-gatsby-preview-server/
- https://www.gatsbyjs.com/preview/

## Build URLs & Build Trigger

This option allows you to configure multiple Gatsby Build Servers. There is a
dropdown that allows you to specify how builds are triggered.

- Incremental Builds: Incremental builds means that a build is triggered anytime
content is changed on your site (you can configure it to only trigger when
content is published). This means your content editors can deploy their content
immediately and don't have to wait for long build times. Incremental Builds only
works with Gatsby Cloud (https://gatsbyjs.com).
- Cron Builds: Builds can be triggered on every cron run.
- Manual Builds: Builds can be triggered by the built in Drush command (ex:
`drush gatsbuild` to trigger all manually set Gatsby Endpoints, see drush help
for additional options).

## Build Entity Types and Included Entity Types

Build Entity types are the entities that you want to be able to trigger a build.
An example of this would be content on your site.

Included Entity Types are entities you want passed along to your Gatsby site
but don't want to to be able to trigger a build. Examples of this include Files,
Media, and possibly Paragraphs.

A simple example: You might have a lot of Files or Media items
on your site, by making this an entity type that is only included and selecting
content types as your Build entity types, you don't have to worry about Gatsby
downloading and processing all of the file/media items on the site. Gatsby will
only download and process file/media items that are attached to content you care
about.

## Gatsby Endpoint Reference Field

The Gatsby Endpoints admin interface makes it easy to send different entity
types to different Gatsby sites, however, you may need to pass one content type
to different endpoints depending on the content. In fact, you may want one piece
of content to be sent to multiple endpoints. The Gatsby Endpoint Reference Field
makes that possible.

The Gatsby Endpoint Reference field is essentially an Entity Reference field
that displays a selectable list of Endpoints. Simply add a field to a content
type or any other entity type and you can then select what Endpoint you want
that entity to end up on. If you don't add a Gatsby Reference Field then all
entities of that type will be part of the Gatsby Endpoint. If you do have
a Gatsby Endpoint Reference field than only the selected entities will be part
of the Gatsby Endpoint.

# Technical Details

Gatsby Endpoints is really just a GUI for building complex filters/includes
query strings for JSON:API resources.

- https://www.drupal.org/docs/core-modules-and-themes/core-modules/jsonapi-module/includes
- https://www.drupal.org/docs/core-modules-and-themes/core-modules/jsonapi-module/filtering
