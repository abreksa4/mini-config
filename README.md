# mini-config

mini-config takes a list of files and directories, and becomes an ArrayAccess object with the parsed config data. 

Currently supports JSON, INI, PHP arrays, and XML** out of the box.

**JSON must be in the following format, though you can register a custom JSON parser to override this:*
```
{
  "cat1": {
    "key1": "value1",
    "key2: "value2"
  },
  "cat2": {
    "key1": 0
  }
}
```

***XML must be in the following format, though you can register a custom XML parser to override this:*
```
<root>
    <key>value</key>
    <cat>
        <key>value</key>
    </cat>
</root>
```

Documentation is available at: [http://abreksa4.github.io/mini-config-docs/](http://abreksa4.github.io/mini-config-docs/)

mini-config merges the config data recursively. (Meaning that if two sources (files) share keys, the values will be merged as an array as well.)

## Installation
Add this to your composer.json

```
{
    "require": {
        "abreksa4/mini-config": "v0.2"
    }
}
```


## Usage

### Create a new Config instance
Create a new Config instance, passing in the optional $options array, which currently supports the keys 'targets' which should contain an array of targets, and
'handlers' an array of handlers in the format [$extension => $handler]. 
```
$config = new Config([
    'targets' => [
        'module/config.xml',
        'config',
        ]
    ],
    'handlers' => array(
        'yml' => function ($file) { return yaml_parse_file($file); }
    )
));
```
At this point the `$config` object is up and running, with the data from the files in `config`,  and in `module/config.xml`.
(Note, we'd need to have the YAML PHP extension installed to use `yaml_parse`.)

### Add more targets
We can add more targets by calling `addTarget`.
As you can see we can either add an array of targets or just one.
```
$config->addTarget('/anothermodule/config');
$config->addTarget(['config_ini', '../config/local']);
```

### Custom handlers
You can register a custom handler for any file extension. For example: 
```
$config->registerHandler(['yml'], 
       function($file){
            return yaml_parse_file($file);
       }
);
```

Notice we can register an array of extensions to one handler, we can also specify a single extension as a string. Extensions are case-sensitive.

### Merge array into config
```
$config->merge([
    'cat3'=> [
        'key1' => 'value1';
    ]
]);
```

### Refreshing the config
Instead of re-scanning and importing all the data every time we add a target, we call the `refresh()` method to re-import the data:
```
$config->refresh();
```
### Data access
We can access the data by treating the `$config` object as an array, i.e.

```
$config['database']['password'];
```
