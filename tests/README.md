# RGPD attribute/metadata regression tests

## Security runtime regression suite

```sh
TMPDIR=/path/to/isolated/scratch php -d error_reporting=-1 -d display_errors=1 tests/security-runtime.php /path/to/vendor/autoload.php
```

Requires the bundle's dependency tree plus Symfony FrameworkBundle, SecurityHttp, SecurityCsrf, PasswordHasher and ErrorHandler (tested with Symfony 7.4 and PHP 8.5). Optional second argument selects one named case. The source PSR-4 mapping is prepended; the installed bundle is not modified.

Covers magic and legacy parent serialization, child magic envelopes with group roles, nullable/disabled users, legacy four-value sessions, form credentials and failure sessions, Azure failure sessions and lazy client selection. Real Symfony password/CSRF listeners accept synthetic valid inputs and reject invalid inputs. CSRF, login-attempt, remember-me and password-upgrade badges remain present. Azure redirect is an offline boundary double: no provider or external network is used. Controller rendering is intercepted, not a Twig/browser test. No kernel or database is used.

**Session compatibility:** new parent payloads append username and permissions to the existing four positional fields. Existing child envelopes can continue calling parent serialization. Old four-value payloads are readable, but lack a trustworthy username/permissions: they restore an empty identifier, no direct permissions and `enabled=false`, requiring fresh authentication; email is never substituted for username. Child group persistence stays the child's responsibility. Do not claim old authenticated sessions survive this change.

The `bundle_build_no_deprecation` diagnostic loads the unmodified bundle under Symfony DebugClassLoader and calls inherited `build()`. No WDUserBundle `build()` deprecation was reproduced on the tested Symfony 7.4 dependency tree, so no speculative override was added.

The WDUser source retains its existing CRLF line endings. Check whitespace without treating CRLF as trailing whitespace: `git -c core.whitespace=cr-at-eol diff --check` (does not change repository configuration).

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
