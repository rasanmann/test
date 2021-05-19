# YQB

## Dependencies

- The will to become a Drupalista (make sure you have it by acknowledging the next picture [Verify willingness to work on Drupal](https://66.media.tumblr.com/037e7e826ff9cd1934049b4fb402d5c5/tumblr_nkz6skXwb51qdk3y4o1_1280.png))

- [Docker](https://www.docker.com/)

- [Docker compose](https://docs.docker.com/compose/)

- [Kubeo](https://gitlab.libeo.com/libeo/kubeo)

#### So you want to become a Drupalista anyway...

- Please follow the next steps to ensure you become one

## Acquia

- Acquia &#174; is a registered trademark and a plateform to maintain and easily deploy a Drupal based website. The website of aeroportdequebec.com is maintain on that plateform. That being said, when one wants to clear cached or do anything related to the project on a dev, staging or production environnement, he, she must login on the plateform.

- You can get the credentials to login on the project within acquia at this address: [cestquoilemotdepasse](https://cestquoilemotdepasse.libeo.com/)  and get credentials to login on acquia's plateform; https://www.acquia.com/ (Dries Buytaert's living dream...)

## Add your ssh key for the project on Acquia's plateform

- Add your public ssh key [here](https://cloud.acquia.com/app/profile/ssh-keys)

- Click `add ssh key` and add a name (as an example: hlegare) and your public key. If you dont know your public key you can get it printed at your terminal with the following command `cat ~/.ssh/id_rsa.pub`

- __Copy__ the output and __paste__ with in the area awaiting your tasteful public key

- Then click `ADD KEY`

- If you did success to copy and paste a string like a good boy, you should be able to __clone__ the project and access the acquia's instance by __ssh__ protocol

- Now go back to your acquia project's mainpage and click on the small git icon on the right next to the `ADD DATABASE` button called `GitInfo`.

> ATTENTION! acquia ssh server accept connection to port 22, Basically it means that if you already some configurations for your ssh to access Libeo's infrastructure which accept port 36220 , we strongly suggest that you add a configuration to connect to acquia easily without specifying the port on every connection
> As an example i used to have this configuration for acquia. Simply copy and paste the next configuration in your config file `~/.ssh/config`
> ```
> Host * !github.com !svn-23659.prod.hosting.acquia.com
>   Port 36220
> ```

- Finally, __Copy__ the __git url__ which should look like `yqb@svn-23659.prod.hosting.acquia.com:yqb.git` and __paste__ it to you terminal to clone the project.

## Installation

- The project can easily be setup locally thanks to @mquesnel with the help of __Kubeo__ (specified at the top of that readme as a dependency)

- After cloning the project, move into the project's folder and run the following command `kubeo start`

## Useful command

- At some point if you would like to sync the project again, one could simply run the following command `kubeo sync`

## If you would like to create an admin account, use the following

Replacer [USERNAME] [EMAIL] [PASSWORD] and run :

```bash
./drupal user-create [USERNAME] --mail="[EMAIL]" --password="[PASSWORD]"
./drupal user-add-role "administrator" [USERNAME]
```
## Work locally like a real Drupalista
- Connect to your running container and set the website in `dev` mode
```bash
docker exec -it yqb_php-fpm bash
cd /app
./vendor/bin/drupal site:mode dev
```

## Rebuild the cache

- Connect to a running container which should be named `yqb_php-fpm`
```bash
docker exec -it yqb_php-fpm bash
cd /app
./vendor/bin/drupal cr
```

## Deployment
- As for deployment, for the `Production` environnement, simply create a tag and push it to acquia. Then you can connect to that instance and clear the cache using the backend
service of Drupal or by connecting by ssh and reach the __drush__ executable at this location `/var/www/html/yqb.prod` and simply run
```
./vendor/bin/drush cr
```

- Then do not forget to clear the __varnish__ service on the instance by clicking on the instance name which will be `Dev` `Stage` or `Prod`
and click the right button `CLEAR VARNISH`

## Assets
- The main theme is called `YQB` and its assets are compile with [gulp](https://github.com/gulpjs/gulp)
- If you modify a sass file make sure to run `gulp`.
- Sadly, the website being hosted on Acquia's plateform, we must __recompile__ the file named __style.css__ and __commit__ the file before every push to the plateform.

## Important documents

- The website was previously made by a company named Cossette and they gave the airport 2 importants documents to better understand the plateform.
- Be aware that those files gets deprecated day by day but might still be useful to better understand some part of the website.
- They are made available here [Document technique](https://projets.libeo.com/attachments/download/68285/Documentation_Technique.pdf), [Aide memoire](https://projets.libeo.com/attachments/download/68286/YQB%20Aide-me%CC%81moire.pdf)

## If suddenly you feel like you need some comfort because you get nothing from that CMS, you can find some comfort here
- [Comfort](https://images.unsplash.com/photo-1557773910-e340bfebbe62?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=675&q=80)


## Access the website

- [Local website](http://yqb.local.vici.io/)
- [Local website's admin](http://yqb.local.vici.io/user/login)

## Deployment

See [Acquia's website](https://cloud.acquia.com)

## Front-end

### Enable Twig debug
Create a `service.yml` by duplicating the default config.
`cp default.service.yml service.yml`
Edit `twig.config` value as follow: `debug: true`, `auto_reload: true`, `cache: false`
Empty caches with `kubeo run drush cr`

### Compiling assets
`cd docroot/themes/custom/yqb`
`yarn start` will run the default gulp task
