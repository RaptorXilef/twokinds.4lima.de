sass --no-source-map main.scss main.css && npx clean-css-cli --source-map -o main.min.css main.css
sass --no-source-map main_dark.scss main_dark.css && npx clean-css-cli --source-map -o main_dark.min.css main_dark.css
sass --no-source-map character_display.scss character_display.css && npx clean-css-cli --source-map -o character_display.min.css character_display.css
sass --no-source-map character_page.scss character_page.css && npx clean-css-cli --source-map -o character_page.min.css character_page.css
sass --no-source-map cookie_banner.scss cookie_banner.css && npx clean-css-cli --source-map -o cookie_banner.min.css cookie_banner.css
sass --no-source-map cookie_banner_dark.scss cookie_banner_dark.css && npx clean-css-cli --source-map -o cookie_banner_dark.min.css cookie_banner_dark.css
