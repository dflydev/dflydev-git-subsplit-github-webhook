Git Subsplit GitHub WebHook
===========================

[GitHub][1] WebHook for Git subsplits managed by [dflydev-git-subsplit][2].

Automates the process of keeping one-way read-only subtree splits up to date
with the source repository.

The WebHook works in two parts, a web listener and a worker. The web listener
adds requests to Redis and the worker processes the requests.

The worker will interact with the system's git as the user running the worker.
This means that the user running the worker should have its key added to
the appropriate GitHub accounts.

During testing it would make sense to run the worker manually. For production
deployments it would probably make more sense to runt he worker using something
along the lines of [upstart][6] or [supervisor][7].


Usage
-----

### git-subsplit

Ensure that [dflydev-git-subsplit][2] is installed correctly. If is not available
in your version of git (likely true for versions older than 1.7.11)
please install it manually from [here][5].


### Installation

#### If You Already Have Composer

    composer create-project dflydev/git-subsplit-github-webhook \
        --keep-vcs -n -s dev webhook
    cd webhook

#### If You Need Composer

    curl -s https://getcomposer.org/installer | php
    php composer.phar create-project dflydev/git-subsplit-github-webhook \
        --keep-vcs -n -s dev webhook
    cd webhook

### Redis

Ensure that the Redis server is running.

### Configure

Copy `config.json.dist` to `config.json` and edit it accordingly. Please make sure
to pay special attention to setting `working-directory` correctly.

### Web Server

Setup a virtual host pointing to `web/` as its docroot. Assuming the virtual host
is **webhook.example.com**, test the WebHook by visiting the following URL:
**http://webhook.example.com/subsplit-webhook.php**

### Worker

Start the worker by running `php bin/subsplit-worker.php`.

### GitHub

From your repository go to **Settings** / **Service Hooks** / **WebHook URLs**.
Enter the URL to your WebHook and click **Update Settings**.

Click **WebHook URLs** again and click **Test Hook**.

If everything is setup correctly the Worker should give you some sort of feedback.


Configuration
-------------

### Example

```
{
    "working-directory": "/home/myuser/.git-subsplit-working",
    "allowed-ips": ["127.0.0.1"],
    "projects": {
        "sculpin": {
            "url": "https://github.com/sculpin/sculpin",
            "repository-url": "git@github.com:sculpin/sculpin.git",
            "splits": [
                "src/Sculpin/Core:git@github.com:sculpin/core.git"
            ]
        },
        "react": {
            "url": "https://github.com/reactphp/react",
            "splits": [
                "src/React/EventLoop:git@github.com:reactphp/event-loop.git",
                "src/React/Stream:git@github.com:reactphp/stream.git",
                "src/React/Cache:git@github.com:reactphp/cache.git",
                "src/React/Socket:git@github.com:reactphp/socket.git",
                "src/React/Http:git@github.com:reactphp/http.git",
                "src/React/HttpClient:git@github.com:reactphp/http-client.git",
                "src/React/Dns:git@github.com:reactphp/dns.git"
            ]
        }
    }
}
```

### Schema

#### working-directory

*String. Default: None. **Required.***

The directory in which the subsplits will be processed. This is more or less
a temporary directory in which all projects will have their subsplit initialized.

#### allowed-ips

*Array. Default: ['207.97.227.253', '50.57.128.197', '108.171.174.178']*

The IP addresses that are allowed to call the WebHook. The default values are
GitHub's IP addresses published here.

#### projects

*Object. **Required**.*

An object whose keys are project names and values are a project description
object.

Project names should only contain a-z, A-Z, 0-9, `.`, `_`, and `-`.

Each project description object can have the following properties:

 * **url**:
   The URL for the project. The WebHook will check each incoming request's
   `url` property against each project's listed `url` property to determine
   which project the request is for.
   
   This URL will look like: **https://github.com/sculpin/sculpin**
   
   Note: The URL is secure (http**s**) and does not contain `.git` extension.
 * **repository-url**:
   The URL that `git` will use to check out the project. This setting is
   optional. If it is not defined the repository URL will be read from the
   incoming request.
 * **splits**:
   An array of subsplit definitions as defined by [dflydev-git-subsplit][2].
   The pattern for the splits is `${subPath}:${url}`.


License
-------

MIT, see LICENSE.


Community
---------

If you have questions or want to help out, join us in the
**#dflydev** channel on irc.freenode.net.


Not Invented Here
-----------------

This project is based heavily on work originally done by [igorw][4].
Thanks Igor. :)


[1]: https://github.com
[2]: https://github.com/dflydev/git-subsplit
[3]: http://getcomposer.org
[4]: https://igor.io
[5]: https://github.com/apenwarr/git-subtree
[6]: http://upstart.ubuntu.com
[7]: http://supervisord.org
