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