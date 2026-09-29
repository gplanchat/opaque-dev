# Hugo site (Opaque user guide)

Publishes the **end-user** documentation from **`../documentation/user/`** with **Hugo** (extended)
and the **hugo-book** theme, fetched as a Go module. ADRs and WAs are not part of the site.

```bash
cd hugo-docs && hugo server
```

## Publication

Not published yet: there is no host and no deployment workflow. `.github/workflows/docs.yml` only
builds the site, so a broken page fails the pull request that broke it. When a host is chosen, set
`baseURL` in `hugo.toml` and add the deployment step with its secrets — a supervised change.
