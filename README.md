# GraphQL Common Types

## Requirements

    * OXID eShop 6.x.x
    * GraphQL Base Module https://github.com/OXID-eSales/graph-ql-base-module

## Install using a local repository

* Add the graphql endpoint in the .htaccess (line 6)
    ```
            RewriteRule ^(graphql/)    widget.php?cl=graphql   [NC,L]
    ```

* Create a local directory if not exists for repositories in your project,
    e.g. `oxideshop/extensions`.
* Check-out this module and move it to the directory you just created
* Add the repository to your project's composer.json, e.g. like this:

  ```json
    "repositories": {
        "oxid-professional-services/graphql": {
            "type": "path",
            "url": "extensions/graphql/"
        }
    }
  ```

  or

  ```bash
    composer config repositories.oxid-professional-services/graphql path extensions/oxps/graphql
  ```
## Require
  ```bash
    composer require oxid-professional-services/graphql:"@dev"
  ```
## Activate

Activate the module in administration area.

## Example
Visit your shop url https://localhost/graphql
you should see the welcome message:

  ```json
  {
      "data": {
          "Message": "Your OXID GraphQL endpoint is ready! Login in the Admin site and use GraphiQL to browse API"
          }
  }
  ```

Example query:
  ```graphql
    {
      categories {
        id
        title
      }
    }
   ```


## Uninstall

Disable the module in administration area and delete module folder.
  ```bash
    composer remove oxid-professional-services/graphql
  ```

## Credits

    * Author: OXID Professional Services
    * URL: www.oxid-esales.com
    * Mail: ps@oxid-esales.com
    * Copyright: (C) OXID eSales AG 2018
