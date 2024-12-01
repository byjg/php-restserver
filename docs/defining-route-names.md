# Defining a Route Name

You can define route with constant and/or variable. For example:

| Pattern                 | Description                                                |
|-------------------------|------------------------------------------------------------|
| `/myroute`              | Matches exactly "/myroute"                                 |
| `/myroute/{id}`         | Matches /myroute + any character combination and set to ID |
| `/myroute/{id:[0-9]+}`  | Matches /myroute + any number combination and set to ID    |

All variables defined above will be available as a parameter. In the example above,
if the route matches the "id" you can get using `$request->param('id');`

Creating the pattern:

- `{variable}` - Match anything and sets to "variable".
- `{variable:specific}` - Match only if the value is "specific" and sets to "variable"
- `{variable:[0-9]+}` - Match the regex "[0-9]+" and sets to variable;

all matches values can be obtained by

```php
$this->getRequest()->param('variable');
```

## Best Practices for Route Names

- Use lowercase for all route names.
- Use hyphen to separate words.
- Use nouns to define the route name and the method to define the action.
- Generally, Use plural nouns do the route name.
- Forwards slashes are used to define a hierarchy of resources.
- Use query parameters to filter the list of items.

e.g.

| Method | Route Name              | Description                    |
|:-------|:------------------------|:-------------------------------|
| GET    | `/products/{id}`        | Get a specif product           |
| GET    | `/products`             | Get a list of product          |
| POST   | `/products`             | Create a new product           |
| PUT    | `/products`             | Update a product               |
| DELETE | `/products`             | Delete a product               |
| GET    | `/products/{id}/images` | Get the images of a product    |
| GET    | `/products?page=1`      | Get the first page of products |
