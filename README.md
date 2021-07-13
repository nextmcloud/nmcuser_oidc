# NextCloud utility app to create OpenId Connect users before login
Creation of users is sometimes required before login e.g. if data is migrated

The app provides a REST API that can only be used with an administrative login.

# API description 
Notes: 
- The `ìd` could be either the username that is received later by the OpenId Connect token or
  the generic identifier hash created within Nextcloud that combines the username with the used provider.
- The `providername` is the provider identifier
  The identifier is matched ignoring upper/lowercase letters, so `MyProvider` matches `myprovider`. 



## Create OpenID Connect user

Create a new user with generic user_oidc username, email and quota.

**URL** : `/apps/nmcuser_oidc/api/1.1/nmcusers/{providername}`

**Method** : `POST`

**Auth required** : YES

**Permissions required** : Admin

### Example request 
`POST /apps/nmcuser_oidc/api/1.1/nmcusers/testprovider`

```json
{
    "username" : "'opattern",
    "displayname" : "Oliver Pattern",
    "email" : "primeolli@pattern.cloud", 
    "quota" : "25GB", // 3GB if not given, we don´t want to have unlimited quota
    "altemail" : "secolli@pattern.cloud", // optional 
    "enabled" : false // optional parameter, default = true
}
```

### Success Responses
**Code** : `200 OK`

```json
{
    "id" : "0066786785671...<NextCLoud internal user hash>",
}
```


### Error Response

**Condition** : Try to create an already existing user.

**Code** : `409 CONFLICT`

## Get an OpenID Connect user

Get the details for an username or id.

**URL** : `/apps/nmcuser_oidc/api/1.1/nmcusers/{providername}/{id}`

**Method** : `GET`

**Auth required** : YES

**Permissions required** : Admin

### Example request
`GET /apps/nmcuser_oidc/api/1.1/nmcusers/testprovider/opattern`

### Success Responses
**Code** : `200 OK`

```json
{
    "id" : "0066786785671...<NextCLoud internal user hash>",
    "displayname" : "Oliver Pattern",
    "email" : "primeolli@pattern.cloud", 
    "quota" : "25GB",
    "altemail" : "secolli@pattern.cloud", // optional 
    "migrated" : false, // optional parameter, default = true
    "enabled" : false // optional parameter, default = true
}
```

### Error Response

**Condition** : User does not exists.

**Code** : `404 NOT FOUND`



## Update an OpenID Connect user

Update all information about a user without changing id or username.

**URL** : `/apps/nmcuser_oidc/api/1.1/nmcusers/{providername}/{id}`

**Method** : `PUT`

**Auth required** : YES

**Permissions required** : Admin

### Example request
`PUT /apps/nmcuser_oidc/api/1.1/nmcusers/testprovider/opattern`

```json
{
    "displayname" : "Oliver Puttern", // all optional
    "email" : "primeolli@pattern.biz", 
    "quota" : "1TB",
    "altemail" : "secolli@pattern.cloud",
    "migrated" : false, 
    "enabled" : true
}
```
You can selectively send any combination of fields, except 'enabled'.
If you want to keep a disabled user disabled, you have to explicitly set ''enabled': false'
again.

### Success Responses
**Code** : `200 OK`

### Error Response

**Condition** : User does not exists.

**Code** : `404 NOT FOUND`



## Delete OpenID Connect user

Remove an created or already logged in OpenID Connect user

**URL** : `/apps/nmcuser_oidc/api/1.1/nmcusers/{providername}/{id}`

**Method** : `DELETE`

**Auth required** : YES

**Permissions required** : Admin

### Example request
`DELETE /apps/nmcuser_oidc/api/1.1/nmcusers/testprovider/opattern`

### Success Responses
**Code** : `200 OK`

### Error Response

**Condition** : User does not exists.

**Code** : `404 NOT FOUND`



## Get a token to access the cloud storage as given user

Get a token of the given user as admin. This is needed to put migrated data into the folder
with the given user as owner of the data.

**URL** : `/apps/nmcuser_oidc/api/1.1/token/{providername}/{id}`

**Method** : `GET`

**Auth required** : YES

**Permissions required** : Admin

### Example request
`GET /apps/nmcuser_oidc/api/1.1/token/testprovider/opattern`

### Success Responses
**Code** : `200 OK` (user deleted)

```json
{
    "token" : "006678HGywq6785AG71...<NextCLoud device app token>",
}
```

### Error Response

**Condition** : User does not exists.

**Code** : `404 NOT FOUND`