##
## OAuth2 And OpenID Connect Framework
## -----------------------------------
##

install: ## Install the dependencies (dev included)
install: vendor

##
## Coding Standard
## ---------------
##

cs: ## Fix coding standard
cs: vendor
	vendor/bin/php-cs-fixer fix

##
## Tests
## -----
##

test: ## Run unit and functional tests
test: tu tf

tu: ## Run unit tests
tu: vendor
	vendor/bin/phpunit --group Functional
	vendor/bin/phpunit -c webfinger.phpunit.xml --group Functional
	vendor/bin/phpunit -c oauth2_security.phpunit.xml --group Functional
	vendor/bin/phpunit -c oauth2_server.phpunit.xml --group Functional

tf: ## Run functional tests
tf: vendor
	vendor/bin/phpunit --exclude-group Functional
	vendor/bin/phpunit -c webfinger.phpunit.xml --exclude-group Functional
	vendor/bin/phpunit -c oauth2_security.phpunit.xml --exclude-group Functional
	vendor/bin/phpunit -c oauth2_server.phpunit.xml --exclude-group Functional

.PHONY: install tu tf help
.DEFAULT_GOAL := help
help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'



composer.lock: composer.json
	composer update

vendor: composer.lock
	composer install

