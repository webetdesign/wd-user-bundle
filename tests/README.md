# RGPD attribute/metadata regression tests

Run with PHP 8.2+ and an installed dependency tree that provides Doctrine ORM 2.20 or 3.x, Symfony Cache/Config/DependencyInjection/EventDispatcher/Filesystem/Finder/Routing and ext-zip:

```sh
TMPDIR=/path/to/isolated/scratch php -d error_reporting=-1 -d display_errors=1 tests/doctrine-attributes.php /path/to/vendor/autoload.php
```

The Composer loader prepends this checkout's `src/` mapping before the installed WD User bundle. An optional second argument selects one case (e.g. `export_attributes`). No application kernel is booted; no connection is opened and no SQL, flush or schema operation is performed. `persist()` only schedules synthetic entities in the real EntityManager's in-memory UnitOfWork. The final assertion requires the connection to remain disconnected.

Coverage:
- class/property Exportable attributes, renamed fields, unmarked properties/classes, null and empty relations;
- all four association cardinalities with SET_NULL/CASCADE (eight combinations), recursive export and cyclic graphs;
- anonymization selection, RGPD timestamps and scheduled persistence;
- Vich type delegation and existing Sonata type gate/recursive behavior using a synthetic file exporter (not real Vich/Sonata integrations);
- public JSON export and ZIP creation/readback, UID-prefixed entries, file contents, absolute download URL, and absence of an archive URL when no file exists;
- source-level guards against the removed ClassMetadataInfo symbol and mapping representation coupling. These guards do not replace running the same behavioral tests with an ORM 3 dependency tree.

`doExport` and the association action matrix use Reflection to isolate the relevant service slices. Archive coverage additionally calls public `export()` with a real Symfony Router/Container. Synthetic attachment and ZIP artifacts are retained under TMPDIR for inspection. Run with an explicitly isolated TMPDIR, not the system temporary directory. HTTP authorization/download, production data, real uploads, database persistence and application boot are outside this standalone suite.
