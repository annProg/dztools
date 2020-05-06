# Sitemap for DiscuzX

修改自 https://www.discuz.net/thread-2474942-1-1.html ，支持 X3.4.

## rewrite注意事项

discuz rewrite 规则中有 

```
if (!-e $request_filename) {
	return 404;
}
```

文件名不存在时返回 404，所以 sitemap 的 rewrite 规则应放在 discuz rewrite 之前：

```
rewrite ^/(forum|group|home|portal)_sitemap\.xml /sitemap/$1_sitemap.xml last;
rewrite ^/([a-z]+)_sitemap_([0-9]+)\.xml  /sitemap/$1_sitemap_$2.xml last;1
```
