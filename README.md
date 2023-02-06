# Reconnect Vault

## Installation

To install project, follow these steps :

[Basic Symfony/Mysql project setup steps](https://ambroise.reconnect.fr/books/modes-operatoires/page/installation-projet-symfonymysql)

## Start developing

* Dump frontend routes

```bash
symfony console fos:js-routing:dump --format=json --target=public/js/fos_js_routes.json
```

* Start PHP and Webpack servers

```bash
symfony serve -d && yarn dev-server
```

* Browse the website [https://localhost:8000](https://localhost:8000)
