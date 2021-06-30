
# IIIF Assemble


<img src="https://digital.lib.utk.edu/iiif/2/collections~islandora~object~tenncities%3A343~datastream~OBJ/full/!400,400/0/default.jpg" alt="Cabin near Knoxville" />

This app assembles and serves IIIF Presentation API 3.0 Manifest and Collections from a Fedora 3.8 objects with a MODS datastream. 

Where `namespace` is a string and `id` is positive integer:

- **Manifest**: `/assemble/manifest/{namespace}/{id}` 
- **Collection**: ```/assemble/collection/{namespace}/{id}```

## Manifest

The example route of `/assemble/manifest/tenncities/343` will correlate to **tenncities:343**, ex: https://digital.lib.utk.edu/assemble/manifest/tenncities/343

## Collection

The path for a collection with the PID `gsmrc:thompson` would be `/assemble/collection/gsmrc/thompson`, ex: https://digital.lib.utk.edu/assemble/collection/gsmrc/thompson. Embedded collections are currently not supported.


## Notes and To Dos

Note: This is not production ready.


### Notes
- This only outputs manifests and metadata fields mapped for boutique purposes.
- This is not currently intended as an access tool for all UT Libraries' collections in the wild.
- This currently does not create collection lists of multiple manifests.
- This generator caches a manifest for 180 days. If metadata or OBJ datastreams are updated, the directory for the manifest must be cleared at `./cache/namespace/id`

### To Do
- Though possible at some point, this generator has no current way creating a manifest with referenced annotation lists.

## Requirements

- PHP 7
- Composer
- Fedora 3.8
 
## UT Libraries Developer Setup

For easiest testing and development, use with the custom Islandora vagrant, [utk_digital](https://github.com/utkdigitalinitiatives/utk_digital). Currently, you must use a feature branch **iiif_assemble** if using this way.
This application can be used independent the *utk_digital* vagrant and Islandora 7, though testing this way is more difficult and currently not outlined.

Note, using `utk_digital` will not require you to install this repository
separate as the latest version of `main` is automatically cloned and installed
upon `vagrant up` in *Step 4* of **Setup**.

### Get the utk_digital vagrant

1. `git clone git@github.com:utkdigitalinitiatives/utk_digital.git`
2. `cd utk_digital`
4. `vagrant up`

### Add the collection record

1. Browse to http://localhost:8000/collections/islandora/object/islandora%3Aroot
2. Click **Manage**
3. Click **Add an object to this Collection**
4. Insert Collection PID as *namespace:collection* and click **Next**
5. Insert Sample Title for Collection Title and click **Ingest** at the bottom

### Ingest a couple of sample records

```
vagrant ssh
cd $DRUPAL_HOME
drush en islandora_scg -y
drush iscgl --user=admin --quantity=5 --content_model=islandora:sp_basic_image --parent=collection:thing --namespace=thing
```

### Allow .htaccess overrides

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
