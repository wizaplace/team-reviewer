PORT = 8482
PWD = $(shell pwd)
CONTAINER_NAME = team-reviewer
IMAGE_NAME = wizaplace/team-reviewer

build:
	docker build -t $(IMAGE_NAME) .

start: clean
	docker run -d -v $(PWD)/repos.dat:/var/www/html/repos.dat -v $(PWD)/config.php:/var/www/html/config.php -p $(PORT):80 --name $(CONTAINER_NAME) --restart=always $(IMAGE_NAME)

stop:
	docker stop $(CONTAINER_NAME); true

clean:
	docker wait $(CONTAINER_NAME)
	docker rm $(CONTAINER_NAME)

update: build stop start
