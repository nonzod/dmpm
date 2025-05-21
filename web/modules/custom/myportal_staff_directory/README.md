# AMAP - Staff Directory

Prima di utilizzare il modulo configurare i parametri da passare all'importer:

`/admin/structure/staff-directory/staff-member-importer`

qui si configura anche la retention dei backup che può essere differente per importer.

La lista dei backup per le operazioni di pulizia e restore:

`/admin/structure/staff-directory/staff-member-backup`

Per operazioni di debug e verifica la lista delle entità importate è visibile qui

`/admin/structure/staff-directory/staff-member`

## Import with drush

`drush sm-ir --importer=<importer_machine_name>`