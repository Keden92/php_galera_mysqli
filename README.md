# galera_mysqli
- mysqli class for usage with galera cluster 
- includes loadbalancing

|[!!! for ONLY 2 Node Cluster comment in Line 126 !!!](https://github.com/Keden92/php_galera_mysqli/blob/adf01a7f3c2d41045b5f68b7ee807d6a46ce9a87/galera_mysqli.class.php#L126)|
|                       :---:                       |

## MAIN
- mysqli class for use with galera cluster Database
- also works with Standalone Database Server
- uses "random" for Load-Balancing if no host specified

## USAGE
- simply pass for $host an array of servers
```
$hosts = array("10.0.0.1", "10.0.0.2", "10.0.0.3");
$mysqli = new galera_mysqli($hosts, $user, $pass,...);
```
- for use of en explizit host pass the array_id to $hostindex
```
$hosts = array("10.0.0.1", "10.0.0.2", "10.0.0.3");
$mysqli = new galera_mysqli($hosts, $user, $pass,..., 2);
```

## ADDITIONAL
- addet magic __sleep & __wakeup methods for serialization
- addet method get_selected_db() 
