IMPORTANT

This module is a fork from the unsupported 1.x branch of the Drupal Gatsby module:
https://git.drupalcode.org/project/gatsby/-/tree/8.x-1.x?ref_type=heads.

We rely on the Gatsby Endpoints module (https://www.drupal.org/project/gatsby_endpoints), 
which is a submodule of gatsby 1.x but is removed from 2.x and now lives in 
its own module project : https://git.drupalcode.org/project/gatsby_endpoints.
Work on the 2.x branch of the module was never finished, and 1.x is incompatible 
with gatsby 2.x. 

The Gatsby Endpoints module that ships with gatsby 1.x cannot be uninstalled 
without breaking the Drupal website :( so we instead point Drupal to this tweaked
version of the module. While this does mean necromancing a dead module branch 
(frowned upon in religeous circles), the clock is ticking to complete this project
so there's no appetite to debug and rewrite everything to work with Gatsby 2.x.

Summary of changes from original module:

1. The 'includes=' section of the /gatsby/some-endpoint is cleaned up, removing 
    duplicate and unnecessary field requests and making the URLs much shorter.

2. The GatsbyTriggerPreview.php file no longer bothers building the entity
    relationships to send along with the data, as there's no point sending any 
    data with our build webhooks - there's no Gatsby Cloud anymore to handle it :(

3. API tokens are now supported for all JSON API requests, meaning users with
   sufficient permissions can now request unpublished content.
    
Note that the webhooks still contain the JSON data packet of whatever content
changed, as originally used by Gatsby Cloud's Incremental Builds and Instant Previews
feature. For non-Gatsby Cloud build services, this data isn't used when the webhook
is received - it just triggers a full build every time.

## Making changes

Changes to this module should be made first on the `dev` branch, then commits should
be merged into `main`. To make the latest version available, tag the `main` branch
with the next sequential version number, e.g. `2.0.1`. 

## Deploying this module to the Parks Australia Drupal website

To deploy updates from this repo to our Pantheon Drupal website:

1. In the `parksaustralia-cms` repo's `composer.json` file, ensure the 
   `drupal/gatsby-custom` package version number captures the latest major version
   e.g. `^2.0` to capture the latest `2.*` versions.
2. In the `parksaustralia-cms` repo, run `lando composer update --lock-only` to 
   update to the latest release tag (or whatever command runs `composer` in your local
   development environment).
3. Commit and push the changes. The change to `composer.lock` should trigger Pantheon's
   `dev` environment to run `composer install` to install the latest version. 
   
If nothing changes on the `dev` environment, ensure you tagged the `main` branch and 
not `dev`.

---
# Original Readme from here on

Allows live preview and incremental builds for your Gatsby site built with your
Drupal content.

Live Preview will work with JSON:API (using gatsby-source-drupal) or the GraphQL
module (using gatsby-source-graphql). Incremental builds currenly only works
with JSON:API, however, you can still configure the module to trigger a full
build if you are using GraphQL.

# Gatsby Submodules

The Gatsby module comes included with multiple submodules. Please read below
for an overview of each submodule. Refer to the modules README files inside the
modules/ folder.

* Gatsby JSON:API Instant Preview & Inctremental Builds (gatsby_instantpreview):
this module allows a faster preview experience and incremental build support for
Gatsby sites that use gatsby-source-drupal and JSON:API.
* Gatsby JSON:API Extras (gatsby_extras): provides additional JSON:API features
such as improved menu link support.
* Gatsby Fastbuilds (gatsby_fastbuilds): provides build caching to speed up
Gatsby development and production builds. Refer to the module's README for more
information.
* Gatsby Endpoints (gatsby_endpoints): Allows you to create multiple endpoints
to source content to multiple Gatsby frontends. This allows you to customize
what data is added to a JSON:API endpoint that can be used by
gatsby-source-drupal to source content.

# Installation

1. Download and install the module as you would any other Drupal 8 module.
Using composer is the preferred method.
2. Make sure to turn on the Gatsby Refresh endpoint by enabling the environment
variable 'ENABLE_GATSBY_REFRESH_ENDPOINT' in your Gatsby environment file as
documented in https://www.gatsbyjs.org/docs/environment-variables/
3. It's easiest to use this by signing up for Gatsby Cloud at
https://www.gatsbyjs.com/. There is a free tier that will work for most
development and small production sites. This is needed for incremental builds
to work.
4. You can also configure this to run against a local Gatsby development
server for live preview. You need to make sure your Drupal site can communicate
with your Gatsby development server over port 8000. Using a tool such as ngrok
can be helpful for testing this locally.
5. Install the gatsby-source-drupal plugin on your Gatsby site if using JSON:API
or gatsby-source-graphql if you are using the Drupal GraphQL module. There are
no additional configuration options needed for the plugin to work (besides
enabling the __refresh endpoing as documented above). However, you
can add the optional secret key (JSON:API only) to match your Drupal
configuration's secret key.
```
module.exports = {
  plugins: [
    {
      resolve: `gatsby-source-drupal`,
      options: {
        baseUrl: `...`,
        secret: `any-value-here`
      }
    }
  ]
};
```
6. Enable the Gatsby module. If you are using JSON:API it's recomended to
enable the JSON:API Instant Preview module for a faster preview experience and
incremental builds support. You may also decide to enable the Gatsby Fastbuilds
module for improved build times when running `gatsby develop` or `gatsby build`.
See the README in the gatsby_fastbuilds submodule for more information.
6. Navigate to the configuration page for the Gatsby Drupal module.
7. Copy the URL to your Gatsby preview server (either Gatsby cloud or a locally
running instance). Once you have that, the Gatsby site is set up to receive
updates.
8. Add an optional secret key to match the configuration added to your
gatsby-config.js file as documented above.
9. Optionally add the callback webhook from Gatsby Cloud to trigger your
incremental builds (JSON:API only). You can also check the box which will only
trigger incremental builds when you publish content. You can also enter a
build callback URL in this box (which can be used to trigger build services
such as Netlify).
10. If you are updating the Gatsby Drupal module and are still using an old
version of gatsby-source-drupal, you can select to use the legacy callback.
Note: this will eventually be removed and it's recommended to upgrade your
gatsby-source-drupal plugin on your Gatsby site.
11. Select the entity types that should be sent to the Gatsby Preview server.
At minimum you typically will need to check `Content` but may need other entity
types such as Files, Media, Paragraphs, etc.
12. Save the configuration form.
13. If you want to enable the Gatsby Preview button or Preview Iframe, go to the
Content Type edit page and check the boxes for the features you would like to
enable for that specific content type.

Now you're all set up to use preview and incremental builds! Make a change to
your content, press save, and watch as your Gatsby Preview site magically
updates and your incremental builds are triggered in Gatsby Cloud!

# Known Issues

- If you enable the Iframe preview on a content type it may cause an issue with
BigPipe loading certain parts of your page. For now you can get around this
issue by disabling BigPipe or not using the Preview Iframe.

# Future Roadmap

There are a few features that are in the roadmap. Some of them include:
- Better support for Drupal's GraphQL module (this is in progress)
- Better integration with Drupal's content moderation system to allow more
flexible content editing workflows and preview experiences.

# Support

The best way to get support is to join the #Gatsby channel in Drupal Slack. You
can also use the issue queue on the project page.
