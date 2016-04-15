default: help

help:
	@echo "Usage:"
	@echo "     make [command]"
	@echo "Available commands:"
	@grep '^[^#[:space:]].*:' Makefile | grep -v '^default' | grep -v '^_' | sed 's/://' | xargs -n 1 echo ' -'

code-standards:
	-vendor/bin/php-cs-fixer fix . --verbose --fixers=-phpdoc_params,-multiline_array_trailing_comma,-phpdoc_short_description,concat_with_spaces,multiline_spaces_before_semicolon,ordered_use,short_array_syntax,no_blank_lines_before_namespace,php4_constructor,php_unit_construct,phpdoc_order,short_array_syntax,short_echo_tag --config=sf23

composer-install:
	composer install

composer-update:
	composer update

coverage:
	rm -rf coverage; vendor/bin/phpunit --coverage-html=coverage/ --coverage-clover=coverage/clover.xml

unit-tests:
	vendor/bin/phpunit --testsuite unit

