
# IIIF Assemble


<img src="https://digital.lib.utk.edu/iiif/2/collections~islandora~object~tenncities%3A343~datastream~OBJ/full/!400,400/0/default.jpg" alt="Cabin near Knoxville" />

This app assembles and serves IIIF Presentation API 3.0 Manifests and Collections from a Fedora 3.8 repository. 

The application has three interfaces that are covered below:

- **Manifest**: `/assemble/manifest/{namespace}/{id}` 
- **Collection**: `/assemble/collection/{namespace}/{id}`
- **Metadata Collection**: `/assemble/metadata/{dc:field}/{value}`

## Manifest Interface

The manifest interface generates, or pulls from cache, a IIIF manifest for the corresponding Fedora object. The manifest
structure is determined by the work type of the corresponding object and is described in detail in 
[UTK IIIF Recipes](https://utk-iiif-cookbook.readthedocs.io/en/latest/).

The example route of `/assemble/manifest/tenncities/343` correlates to **tenncities:343**, ex: https://digital.lib.utk.edu/assemble/manifest/tenncities/343

## Collection Interface

The collection interface generates, or pulls from cache, a IIIF manifest for the corresponding Fedora colleciton.  The
manifest structure is described in detail in the 
[UTK IIIF Recipes: Collections Section](https://utk-iiif-cookbook.readthedocs.io/en/latest/contents/collections.html)

The path for a collection with the PID `gsmrc:thompson` would be `/assemble/collection/gsmrc/thompson`, ex: 
https://digital.lib.utk.edu/assemble/collection/gsmrc/thompson.

## Metadata Collection Interface

The metadata collection interface generates a IIIF colleciton manifest based on results on a SPARQL query versus the 
Fedora Resource Index. 

The path for this follows the general pattern above but with the DublinCore field and an associated value.  Some examples:

- [https://digital.lib.utk.edu/assemble/metadata/contributor/Wilson,%20Danny](https://digital.lib.utk.edu/assemble/metadata/contributor/Wilson,%20Danny)
- [https://digital.lib.utk.edu/assemble/metadata/subject/Black%20bears](https://digital.lib.utk.edu/assemble/metadata/subject/Black%20bears)
- [https://digital.lib.utk.edu/assemble/metadata/date/1991](https://digital.lib.utk.edu/assemble/metadata/date/1991)

## Requirements

- PHP 7
- Composer
- Fedora 3.8
 
## UT Libraries Developer Setup

This section outlines the easiest way to get a development environment for working with assemble and Fedora 3.8.

To do this, do not use this repo, but instead clone, configure, and vagrant up 
[utk_digital](https://github.com/utkdigitalinitiatives/utk_digital). 

**Note**: The application can be used independent of the vagrant box,  but testing this way is much more difficult and 
currently not outlined.


### 1. Get the utk_digital vagrant

1. `git clone git@github.com:utkdigitalinitiatives/utk_digital.git`
2. `cd utk_digital`
4. `vagrant up`

### 2. Add a collection record

1. Browse to http://localhost:8000/collections/islandora/object/islandora%3Aroot
2. Click **Manage**
3. Click **Add an object to this Collection**
4. Insert Collection PID as *namespace:collection* and click **Next**
5. Insert Sample Title for Collection Title and click **Ingest** at the bottom

### 3. Ingest a couple of sample records

1. SSH to vagrant
2. Go to drupal
3. Use Drush and Islandora Sample Content Generator

```
vagrant ssh
cd $DRUPAL_HOME
drush en islandora_scg -y
drush iscgl --user=admin --quantity=5 --content_model=islandora:sp_basic_image --parent=collection:thing --namespace=thing
```

### 4. Allow .htaccess overrides

```
vagrant ssh
sudo vim /etc/httpd/conf/httpd.conf
```

Find and modify `AllowOverride None` in

```
<Directory "/vhosts/digital/web">
    Options Indexes FollowSymLinks
    AllowOverride None
    Require all granted
</Directory>
```

to `AllowOverride All` so that it reads as:

```
<Directory "/vhosts/digital/web">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

Then restart the `httpd` service.

```
sudo systemctl restart httpd
```

### 5. Move environmental variable file

Authentication is controlled by a .env file

```
mv /vhosts/digital/web/assemble/.env.example /vhosts/digital/web/assemble/.env
```

### 6. Add cache directory

In order to ensure that requests aren't made after initial generation, each doc is written to cache.

```
mkdir /vhosts/digital/web/assemble/cache
```
