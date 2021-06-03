# Some example tests with curl:
- GET, user not found:
```
curl -i -u apitest:qLagUm0DByDydvL0Svnu -X GET https://int1.next.magentacloud.de/apps/nmcuser_oidc/api/1.0/nmcusers/telekom/12345
```

- GET, provider not found:
```
curl -i -u apitest:qLagUm0DByDydvL0Svnu -X GET https://int1.next.magentacloud.de/apps/nmcuser_oidc/api/1.0/nmcusers/o2/2f9cee4eab29cd216733b3ddde2o2c693730131c9fb1b2f6c893e1ec9b8
```

- GET, already logged in user, anid key
```
curl -i -u apitest:qLagUm0DByDydvL0Svnu -X GET https://int1.next.magentacloud.de/apps/nmcuser_oidc/api/1.0/nmcusers/telekom/2f9cee4eab29cd216733b3ddde29ab209ec693730131c9fb1b2f6c893e1ec9b8
```

- CREATE, with known anid (no existence check):
```
curl -i -u apitest:qLagUm0DByDydvL0Svnu -X POST -H "Accept: application/json" https://int1.next.magentacloud.de/apps/nmcuser_oidc/api/1.0/nmcusers/telekom -d '{"username": "120049010000000006612061", "displayname": "User, Test", "quota": "3GB", "email": "nmcloud02@ver.sul.magenta.de"}'
```

- DELETE, not found
```
curl -i -u apitest:qLagUm0DByDydvL0Svnu -X DELETE https://int1.next.magentacloud.de/apps/nmcuser_oidc/api/1.0/nmcusers/telekom/12345
```

- DELETE, existing user
```
curl -i -u apitest:qLagUm0DByDydvL0Svnu -X DELETE https://int1.next.magentacloud.de/apps/nmcuser_oidc/api/1.0/nmcusers/telekom/
```

- GET token, not found
```
curl -i -u apitest:qLagUm0DByDydvL0Svnu -X GET https://int1.next.magentacloud.de/apps/nmcuser_oidc/api/1.0/token/telekom/12345
```

- GET token, existing user:
```
curl -i -u apitest:qLagUm0DByDydvL0Svnu -X GET https://int1.next.magentacloud.de/apps/nmcuser_oidc/api/1.0/token/telekom/2f9cee4eab29cd216733b3ddde29ab209ec693730131c9fb1b2f6c893e1ec9b8
```
