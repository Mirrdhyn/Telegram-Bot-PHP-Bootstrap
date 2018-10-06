# Telegram-Bot-PHP-Bootstrat

This code, written in PHP, covers all features (almost) of [Telegram Bot API](core.telegram.org/bots/api)

## What's needed

Frontend accessible from Internet with PHP engine (5 or 7) and a SSL certificate, from Let's Encrypt, for instance.
You can use this [docker image](https://hub.docker.com/r/nimmis/apache-php7/) to use your code and add a Nginx front to reverse proxy your bot. In fact, you can use your infrastructure where you are comfortable for, as long as you can get a JSON from HTTP POST request and send it back an answer in the same format.

## How to get Hello from your bot?

[ ] Get a Bot token from https://t.me/BotFather with the command /newbot
Declare your webhook link by typing the following link in your favorite browser :
[ ] Replace `BOTTOKEN` with the token previously created
[ ] Replace `MYLINK` with your _FQDN_ (eg. my.bot.com)
  https://api.telegram.org/botBOTTOKEN/setWebhook?url=https://MYLINK/my_bot.php
[ ] Add the bot token in the `b0tacc3s_guard.php` file
[ ] Add your Telegram ID to the environment variable `CHAT_ROOT` in this file too. To get it, just uncomment the debug line `8` in `my_bot.php` and read the JSON stored in debug folder.

## To go futher

You can custom your webhook setting from Telegram by adding some parameters : [go here](https://core.telegram.org/bots/api#setwebhook)

You can deep-link the /start command. When you are authenticating your user from an OAuth provider, you can add your session token in your link. Eg. https://t.me/my_bot?start=1234token567890

Now, you are ready to modify this base code and add a lot of yours.
