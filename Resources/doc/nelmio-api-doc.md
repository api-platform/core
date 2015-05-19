# NelmioApiDocBundle integration

![Screenshot of DunglasApiBundle integrated with NelmioApiDocBundle](images/NelmioApiDocBundle.png)

[NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle) (since version 2.9) has built-in support for DunglasApiBundle.
Installing it will give you access to a human-readable documentation and a nice sandbox.

Once installed, use the following configuration:

```yaml

# app/config/services.yml

nelmio_api_doc:
    sandbox:
        accept_type:        "application/json"
        body_format:
            formats:        [ "json" ]
            default_format: "json"
        request_format:
            formats:
                json:       "application/json"
```

Previous chapter: [Getting Started](getting-started.md)<br>
Next chapter: [Operations](operations.md)
