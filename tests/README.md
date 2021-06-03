# Tip for logfile filtering:
```
tail -f /var/log/nextcloud/nextcloud.json.log |jq 'select(.user=="apitest")'
```

# Some example tests with curl:
- GET, user not found:
```
curl -i -u apitest:aL***-X GET https://dev2.next.magentacloud.de/apps/nmcuser_oidc/api/1.1/nmcusers/telekom/12345
```

- GET, provider not found:
```
curl -i -u apitest:aL***-X GET https://dev2.next.magentacloud.de/apps/nmcuser_oidc/api/1.1/nmcusers/o2/2f9cee4eab29cd216733b3ddde2o2c693730131c9fb1b2f6c893e1ec9b8
```

- GET, already logged in user, id
```
curl -i -u apitest:aL***-X GET https://dev2.next.magentacloud.de/apps/nmcuser_oidc/api/1.1/nmcusers/telekom/2f9cee4eab29cd216733b3ddde29ab209ec693730131c9fb1b2f6c893e1ec9b8
```

- GET, existing user. anid/username key
```
curl -i -u apitest:aL***-X GET https://dev2.next.magentacloud.de/apps/nmcuser_oidc/api/1.1/nmcusers/telekom/120049010000000006612061
```

- CREATE, with known anid/username:
```
curl -i -u apitest:aL***-X POST -H "Content-Type: application/json" -H "Accept: application/json" --data-raw '{"username": "120049010000000006612061", "displayname": "User, Test", "quota": "3GB", "email": "nmcloud02@ver.sul.t-online.de" }' https://dev2.next.magentacloud.de/apps/nmcuser_oidc/api/1.1/nmcusers/telekom 
```
On second call, there should be a `409 CONFLICT` because user already exists 

- CREATE, with known anid/username, no quota, altemail:
```
curl -i -u apitest:aL***-X POST -H "Content-Type: application/json" -H "Accept: application/json" --data-raw '{"username": "120049010000000006612061", "displayname": "User, Test", "email": "nmcloud02@ver.sul.t-online.de", "altemail": "fool@fool.cloud"}' https://dev2.next.magentacloud.de/apps/nmcuser_oidc/api/1.1/nmcusers/telekom 
```


- PUT update, user not found:
curl -i -u apitest:aL***-X PUT -H "Content-Type: application/json" -H "Accept: application/json" --data-raw '{ "displayname": "User, Test2" }' https://dev2.next.magentacloud.de/apps/nmcuser_oidc/api/1.1/nmcusers/telekom/12345

- PUT update, provider not found:
curl -i -u apitest:aL***-X PUT -H "Content-Type: application/json" -H "Accept: application/json" --data-raw '{ "displayname": "User, Test2" }' https://dev2.next.magentacloud.de/apps/nmcuser_oidc/api/1.1/nmcusers/o2/120049010000000006612061

- PUT update, anid/username key, displayname change only
```
curl -i -u apitest:aL***-X PUT -H "Content-Type: application/json" -H "Accept: application/json" --data-raw '{ "displayname": "User, Test2" }' https://dev2.next.magentacloud.de/apps/nmcuser_oidc/api/1.1/nmcusers/telekom/120049010000000006612061
```

- PUT update, anid/username key, account changes on quota only
```
curl -i -u apitest:aL***-X PUT -H "Content-Type: application/json" -H "Accept: application/json" --data-raw '{ "quota": "1 TB" }' https://dev2.next.magentacloud.de/apps/nmcuser_oidc/api/1.1/nmcusers/telekom/120049010000000006612061 
```

- PUT update, anid/username key, changes on displayname, email and altemail only
```
curl -i -u apitest:aL***-X PUT -H "Content-Type: application/json" -H "Accept: application/json" --data-raw '{ "email": "nmcloud02@ver.sul.magenta.de", "altemail": "fool2@foolish.org" }' https://dev2.next.magentacloud.de/apps/nmcuser_oidc/api/1.1/nmcusers/telekom/120049010000000006612061 
```


- DELETE, not found
```
curl -i -u apitest:aL***-X DELETE https://dev2.next.magentacloud.de/apps/nmcuser_oidc/api/1.1/nmcusers/telekom/12345
```

- DELETE, existing user with id
```
curl -i -u apitest:aL***-X DELETE https://dev2.next.magentacloud.de/apps/nmcuser_oidc/api/1.1/nmcusers/telekom/2f9cee4eab29cd216733b3ddde29ab209ec693730131c9fb1b2f6c893e1ec9b8
```

- DELETE, existing user with anid/username
```
curl -i -u apitest:aL***-X DELETE https://dev2.next.magentacloud.de/apps/nmcuser_oidc/api/1.1/nmcusers/telekom/120049010000000006612061
```

- GET token, not found
```
curl -i -u apitest:aL***-X GET https://dev2.next.magentacloud.de/apps/nmcuser_oidc/api/1.1/token/telekom/12345
```

- GET token, existing user by id hash:
```
curl -i -u apitest:aL***-X GET https://dev2.next.magentacloud.de/apps/nmcuser_oidc/api/1.1/token/telekom/2f9cee4eab29cd216733b3ddde29ab209ec693730131c9fb1b2f6c893e1ec9b8
```

- GET token, existing user by anid/username:
```
curl -i -u apitest:aL***-X GET https://dev2.next.magentacloud.de/apps/nmcuser_oidc/api/1.1/token/telekom/120049010000000006612061
```