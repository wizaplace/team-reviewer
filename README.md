# team-reviewer

Copy config.php.dist to config.php and add your settings.

## Docker

```shell
docker build -t wizaplace/team-reviewer .
docker run -d \
    -v $(pwd)/repos.dat:/var/www/html/repos.dat \
    -v $(pwd)/config.php:/var/www/html/config.php \
    -p 8080:80 \
    wizaplace/team-reviewer
```
