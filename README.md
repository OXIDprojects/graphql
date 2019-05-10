# GraphQl Common Types module

A simple example module for GraphQl queries and mutations. This is also
some sort of tutorial how to write a GraphQl module for OXID.

This README will provide step by step instructions how to implement
things.

The **graph-ql-base-module** is the main framework for using GraphQL in OXID

The code in this branch is altered to be backward compatible
to the 6.1.3 compilation. You need to add the `service.yaml`
files of this and all the other graphql modules you are
using manually to the DI container (the feature to do this
automatically on module installation will be available
in the next minor release). This can be done in
the `Internal\Application\services.yaml` like this:

```yaml
  imports:
    - { resource: ../Utility/services.yaml }
    - { resource: ../Logger/services.yaml }
    - { resource: ../Common/services.yaml }
    - { resource: ../Review/services.yaml }
    - { resource: ../Form/services.yaml }
    - { resource: ../Adapter/services.yaml }
    - { resource: /var/www/oxideshop/source/modules/oe/graphql-base/services.yaml }
    - { resource: /var/www/oxideshop/source/modules/oe/graphql-developer/services.yaml }
    - { resource: /var/www/oxideshop/source/modules/oxcom/graphql-common-types/services.yaml }
```

## The purpose of this module

This is just a simple module to handle categories. We want to be able
to get category listings from the shop and also to be able, to add a
new category to the shop. So in GraphQl terminology: We want to have
some queries and a mutation.

## The steps

### Step 1: Implement the domain logic

You should completeley separate the domain logic from your
GraphQl types. Just think about what information you want
to expose through the GraphQl API and encapsulate it into
data objects.

A data object should be a dumb object just with getters
and setters. So if you look at the `Category` object in the
`DataObject` folder, you see just a typical data object
with three properties: `title`, `id` and `parentid`.

This `Category` object is the container for the data
we want to expose through our GraphQl API. Now we need
to implement some business logic to fill the instances
of this object with data.

In our case here the business logic is quite simple:
Retrieve the data from the database (and later on,
store data to the database). So we do not need a
service layer with complex data logic, but just a
data access object that implements database persistence.
You find the implementation in the `Dao` directory, where
the `CategoryDao` resides, alongside the appropriate
`CategoryDaoInterface`.

Now, before implementing the data access object, think
about the interface you want to provide. In our case
it is quite simple:

```php
 interface CategoryDaoInterface
  {
      public function getCategory(string $categoryId, string $lang, int $shopId);

      public function getCategories(string $lang, int $shopId, $parentid = null);

      public function addCategory(array $names, int $shopId, string $parentId = null);

  }
```

Note that the all methods have a language and
a shop id parameter (in `addCategory` the language
is implicit, because the `$names` array is supposed to
hold names for all languages). This is quite important:
The OXID eShop supports a multi language and multi shop
environment. So each dao or service class should support
different languages and different shopids. In your
implementation you should also honor this, even if
you implement you GraphQl route for a shop that only
has one language and no multi shops. Reusability is
only guaranteed if you support the more advanced features.
And the effort is small, if you do it right from the
beginning.

After designing the interface and creating the dao class
with empty methods, you should first write a test class
for your interface. This is called test driven development
(TDD) and is considered to be good practice. You will
find the test class for the `CategoryDao` in the
`tests\Integration\Dao` folder. After defining the expected
behaviour of you code in the test class, every test
should fail, because you have not implemented anything.
Now implement the complete database logic until all of
your tests are green.

And that's it for domain logic in our small example, because
we straightforward retrieve data from or add data to the database.
If you need to do more complicated stuff, create a two tiered
architecture: Put all the persistence stuff into a data access
object and all the more sophisticated logic into a service,
that gets the dao injected (we talk about dependency injection
below). This service should be created in a separate `Service`
folder, but the principle is the same: First design an interface,
then write a test and finally implement the logic. Only
now you may write genuine unit tests, because you can
mock all the stuff regarding persistence of your data.
You just must make sure that you inject the dao into your
service, so you can mock it in the test.

### Step 2: Implementing the GraphQl type

Actually this is quite easy. You just have to
do some configuration stuff.

If you look at the `CategoryType` (found in the
`Type/ObjectType` folder) we want to use in this
example, it just extends the `ObjectType` of the underlying
GraphQl framework. It only implements the constructor,
where a config array as created and given to the parent
constructur. If you want to understand in detail what
is happening here, just refer to the
[GraphQl documentation](https://webonyx.github.io/graphql-php/),
because this is beyond the scope of this tutorial.

Except with one exception. The type object gets a
class implementing the `GenericFieldResolverInterface`
injected. This class is part of the OXID GraphQl
framework. It is a handy utility for resolving
fields. Look at the `resolveField` instruction in
the configuration. Here an anonymous function is defined that
is called by the framework:

```php
'resolveField' => function ($value, $args, $context, ResolveInfo $info) {
                return $this->genericFieldResolver->getField($info->fieldName, $value);
            }
```

Here again comes dependency injection into play: On the
injected `GenericFieldResolver` you can call the `getField`
method with a fieldname
and a data object. The `GenericFieldResolver` then searches
for a getter method on the data object and calls it.

So the `$value` this anonymous function receives is our
`Category` data object, and from the `ResolveInfo` object
we get the field name. Together with the `GenericFieldResolver`
it is simple to fetch the desired field from the object.

There is not much to test here, so we will test this in
conjecture with the provider class that is explained in
detail in the next section.

### Step 3: Create a provider class

For all data you want to provide as part of your GraphQl
API your need to write a provider class. This binds the
part of the API your module is providing to the overall
GraphQl Framework. The binding itself is done via dependency
injection, but this will be described in the next section.
In this step we describe just the definition and implementation
of queries and mutations (if you do not know what queries
and mutations are, refer to the
[GraphQl Documentation](https://webonyx.github.io/graphql-php/))

The provider class must implement at least the
`QueryProviderInterface` or the `MutationProviderInterface`
or both. The interfaces are quite simple (the
`MutationProviderInterface` is analogous):

```php
interface QueryProviderInterface
{

    /**
     * @return array
     */
    public function getQueries();

    /**
     * @return array
     */
    public function getQueryResolvers();
}
```

The `getQueries()` or `getMutations()` methods just
return an array where you configure the fields
on the query respective mutation GraphQl type. In
a nutshell it is just the type of the field,
a description and the arguments.

And then you need to define the resolvers for
the fields. This is where you call the business
logic that you implemented in
[Step 1](###Step-1:-Implement-the-domain-logic).

Again you have to implement the same function
that you already know from the previous step.
Here is an example from our `CategoryProvider`:

```php
    'category' => function ($value, $args, $context, ResolveInfo $info) {
        /** @var AppContext $context */
        $token = $context->getAuthToken();
        $this->permissionsService->checkPermission($token, 'mayreaddata');
        return $this->categoryDao->getCategory(
            $args['categoryid'],
            $token->getLang(),
            $token->getShopid()
        );
    },

```

Now we use the two other parameters of the
function, the `$args` array and the `$context`
object. From the context you get the
authentication token. You should
always use the permission service to
check if the user has the permission
to do what you implement in the rest
of the function. In this example we
check for the `mayreaddata` permission -
the lowest permission that exists and
is available for all logged in users:

```php
  $this->permissionsService->checkPermission($token, 'mayreaddata');
```

This is all you need to do for a permission
check. If the user is missing the permission
given as second parameter, an exception is thrown
which will result in a GraphQl error that informs
the user that he is missing a certain permission.

The `mayreaddata` permission is part of the framework,
but you may define your own permissions.
You can find an example in the `addCategory`
mutation. In the next step we will show
you how to configure your own permissions.

But you can do more with the token
than only just check permissions. The
token also provides you with a language
and a shop id. And you can be certain,
that the token *always* returns a language and a
shop id, even in a one language / one
shop environment. This allows you to
write your dao/service classes in a
generic way that works in all shop
environments.

> *Caveat*
>
> If you are using shop models to load
> data, you can't provide the language / shopid.
> These are already set on bootstrap.
> Currently there is not yet a mechanism
> in the shop bootstrap to set this from
> an authentication token. But this will
> be implemented soon.

After checking the permissions you should
keep the logic quite simple. All business
logic should already be implemented in your
dao / services classes. Here you shouldn't
do nothing more than perhaps configure
service calls according to arguments of
the query / mutation.

You can test your type / provider quite
easily. Look at the unit test `CategoryTypeTest`.
It inherts from the `GraphQlTypeTestCase` which
provides some helper methods. You can set
permissions before excuting a query; and the
query will be executed with a default context
unless you do provide your own context
when calling the execute method. The setup
method shows you an example how to set up
the schema for testing the queries and mutations
that you provide for your GraphQl Type.

### Step 4: Tying it all together

Now we have all the components together that
we need to implement a part of a GraphQl
API.

* The business logic in data access objects / services
* The GraphQl type that we want to provide
* The query / mutations that use our GraphQl type

The task is now to put it all together. For this
purpose we use the Symfony DI container that
comes with the OXID eShop framework.

The configuration is done in a file called
`services.yaml` that you put into the root
directory of your module. If you are not
familiar with the Symfony DI container, you
should read the
[documentation](https://symfony.com/doc/3.4/service_container.html).

And this is the configuration file:

```yaml
services:

  _defaults:
    public: false
    autowire: true

  OxidCommunity\GraphQl\Dao\Common\CategoryDaoInterface:
    class: OxidCommunity\GraphQl\Dao\Common\CategoryDao

  OxidCommunity\GraphQl\Type\ObjectType\CategoryType:
    class: OxidCommunity\GraphQl\Type\ObjectType\CategoryType

  OxidCommunity\GraphQl\Type\Provider\CategoryProvider:
    class: OxidCommunity\GraphQl\Type\Provider\CategoryProvider
    tags: ['graphql_query_provider', 'graphql_mutation_provider']

  OxidCommunity\GraphQl\Service\CategoryPermissionsProvider:
    class: OxidEsales\GraphQl\Service\PermissionsProvider
    calls:
      - ['addPermission', ['admin', 'mayaddcategory']]
      - ['addPermission', ['shopadmin', 'mayaddcategory']]
    tags: ['graphql_permissions_provider']
```

All of our implementation goes to the `services` section
of the configuration. At the beginning we define two defaults:
None of our services should be public (that means you can't
fetch them from the DI container, which is a good thing;
everything is handled within the container) and that
Symfony should try to autowire everything (that means,
if you type hint the parameters in the constructor of
a service, the container searches for a service that
matches that type hint when instantiating the service).

The next two entries are trivial: We define our data access
object as a service using the interface of the dao as
the service key. That helps autowiring when we type hint
a dependency with this interface. The next service is
equally trivial, it's our category type and since this
does not have an interface, we use the qualified class
name itself as a key.

The next entry is a tad more complicated: It defines
our provider class. Again we use the class name itself
as a key. But in fact this key is quite irrelevant
because this key is never used anywhere. Instead we
add two tags, the `graphql_query_provider` and the
`graphql_mutation_provider`. This tags are used to
inject the information from the provider into the
GraphQl schema. And since we implemented queries and
a mutation in one provider, we need both tags.

And in the last entry we define the permissions
that we want to use. Again, the key is completely
irrelevant, so you may make up something. But it
should be unique, so using the classpath of your
module together with a descriptive name makes sense.
The class itself is always the same, the `PermissionsProvider`
class that comes with the OXID GraphQl framework.
And now you can add permissions in the call section.
The method, that is called, is named appropriately
`addPermission`, and the first parameter is the user
group, the second a permission (which is just the
string you use in your permission check in the
query / mutation resolver).

Currently there are four user groups:

<dl>
<dt>anonymous:</dt>
  <dd>a logged in user without a user account</dd>
<dt>customer:</dt>
  <dd>a logged in user with a user account</dd>
<dt>shopadmin:</dt>
  <dd>a user with administration rights for a certain shop</dd>
<dt>admin:</dt>
  <dd>a user with global administration rights</dd>
</dl>

Look at the `services.yaml` file of the OXID graphql base
module to see which permissions are already set for
these groups.

If everything is tied together, you should write
acceptance tests for all your queries and mutations.
Again there is an example in the `Acceptance` folder
under tests. This `CategoryTest` inherits from
`BaseGraphQlAcceptanceTestCase` that provides a setup
of the DI container. You really need not care about this,
just use the `executeQuery` method to run a query / mutation.
You will find the result of query in three properties
of the test case:

* `$this->queryResult` contains data / errors that the
  client will receive
* `$this-httpStatus` contains unsurpringly the http status the client will
  receive
* `$this->logResult` contains all errors that are normally
  written to the log file

This allows you to write nearly complete end to end
tests without involving an http server.

To run the acceptance tests, you need to activate the
module in the test configuration of your OXID project.
So for this example module, the `test_config.yml` in the root
directory of your project should have the following entries:

```yaml
mandatory_parameters:
    ...
    partial_module_paths: 'oe/graphql-base,oxcom/graphql-common-types'
    ...
optional_parameters:
    ...
    activate_all_modules: true
    ...
```