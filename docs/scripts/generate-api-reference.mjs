import fs from 'node:fs/promises';
import path from 'node:path';

const projectRoot = path.resolve(process.cwd(), '..');
const modulesRoot = path.join(projectRoot, 'modules');
const outputPath = path.join(process.cwd(), 'static', 'api-reference', 'index.json');

const targetModules = ['core', 'media', 'page-builder', 'updater'];

async function walkPhpFiles(dirPath) {
  const entries = await fs.readdir(dirPath, {withFileTypes: true});
  const files = await Promise.all(
    entries.map(async (entry) => {
      const full = path.join(dirPath, entry.name);
      if (entry.isDirectory()) {
        return walkPhpFiles(full);
      }

      if (entry.isFile() && entry.name.endsWith('.php')) {
        return [full];
      }

      return [];
    }),
  );

  return files.flat();
}

function detectLayer(relativeFilePath) {
  const parts = relativeFilePath.split(path.sep);
  const srcIndex = parts.indexOf('src');
  const layerParts = srcIndex >= 0 ? parts.slice(srcIndex + 1, -1) : [];

  if (layerParts.length === 0) {
    return 'Other';
  }

  return layerParts.join('/');
}

function extractClass(content) {
  const match = content.match(/\bclass\s+([A-Za-z0-9_]+)/);
  return match ? match[1] : null;
}

function normalizeDocBlock(docBlock) {
  if (!docBlock) {
    return '';
  }

  return docBlock
    .replace(/^\/\*\*\s*/m, '')
    .replace(/\*\/$/, '')
    .split('\n')
    .map((line) => line.replace(/^\s*\*\s?/, '').trim())
    .filter((line) => line && !line.startsWith('@'))
    .join(' ')
    .trim();
}

function inferClassDescription(layer, className) {
  const classLower = className.toLowerCase();
  const layerLower = layer.toLowerCase();

  if (classLower.endsWith('controller')) {
    return `Handles ${layerLower.includes('api') ? 'API' : 'HTTP'} requests for ${className.replace(/Controller$/, '')}.`;
  }

  if (classLower.endsWith('service')) {
    return `Provides business logic and orchestration for ${className.replace(/Service$/, '')}.`;
  }

  if (classLower.endsWith('request')) {
    return `Validates incoming request payloads for ${className.replace(/Request$/, '')}.`;
  }

  if (classLower.endsWith('resource')) {
    return `Serializes data for API responses related to ${className.replace(/Resource$/, '')}.`;
  }

  if (classLower.endsWith('policy')) {
    return `Defines authorization rules for ${className.replace(/Policy$/, '')}.`;
  }

  if (classLower.endsWith('command')) {
    return `CLI command entry point for ${className.replace(/Command$/, '')}.`;
  }

  if (classLower.endsWith('job')) {
    return `Asynchronous background job for ${className.replace(/Job$/, '')}.`;
  }

  if (layerLower.includes('models')) {
    return `Represents persisted domain data for ${className}.`;
  }

  return `Implements ${className} behavior in ${layer}.`;
}

function splitMethodName(name) {
  return name
    .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
    .replace(/_/g, ' ')
    .toLowerCase();
}

function inferMethodDescription(methodName) {
  if (methodName === '__construct') {
    return 'Initializes class dependencies and runtime state.';
  }

  if (methodName === '__invoke') {
    return 'Handles single-action invocation entry point.';
  }

  if (methodName.startsWith('index')) {
    return 'Returns a collection or listing result.';
  }

  if (methodName.startsWith('show') || methodName.startsWith('view')) {
    return 'Returns details for a single resource.';
  }

  if (methodName.startsWith('store') || methodName.startsWith('create')) {
    return 'Creates a new resource or record.';
  }

  if (methodName.startsWith('update')) {
    return 'Updates an existing resource or state.';
  }

  if (methodName.startsWith('destroy') || methodName.startsWith('delete')) {
    return 'Deletes an existing resource or state.';
  }

  if (methodName.startsWith('authorize')) {
    return 'Checks permission before handling the request.';
  }

  if (methodName.startsWith('rules')) {
    return 'Provides validation rules for request payloads.';
  }

  if (methodName.startsWith('toArray')) {
    return 'Transforms model data into response array format.';
  }

  if (methodName.startsWith('handle')) {
    return 'Executes the main command/job logic.';
  }

  return `Performs ${splitMethodName(methodName)}.`;
}

function extractClassDescription(content, layer, className) {
  const classMatch = content.match(/(\/\*\*[\s\S]*?\*\/)?\s*class\s+[A-Za-z0-9_]+/);
  const fromDoc = normalizeDocBlock(classMatch?.[1] || '');
  return fromDoc || inferClassDescription(layer, className);
}

function extractMethods(content) {
  const methodRegex = /(\/\*\*[\s\S]*?\*\/)?\s*public function\s+([A-Za-z0-9_]+)/g;
  const methods = [];

  for (const match of content.matchAll(methodRegex)) {
    const name = match[2];
    methods.push({
      name,
      description: normalizeDocBlock(match[1] || '') || inferMethodDescription(name),
    });
  }

  const uniqueByName = new Map();
  for (const method of methods) {
    if (!uniqueByName.has(method.name)) {
      uniqueByName.set(method.name, method);
    }
  }

  return [...uniqueByName.values()].sort((a, b) => a.name.localeCompare(b.name));
}

function shouldExcludeClass(layer, className, relativeFile) {
  const layerLower = layer.toLowerCase();
  const classLower = className.toLowerCase();
  const fileLower = relativeFile.toLowerCase();

  return (
    layerLower.includes('http') ||
    fileLower.includes('/http/') ||
    classLower.endsWith('controller')
  );
}

async function build() {
  const entries = [];

  for (const moduleName of targetModules) {
    const moduleSrc = path.join(modulesRoot, moduleName, 'src');
    const phpFiles = await walkPhpFiles(moduleSrc);

    for (const filePath of phpFiles) {
      const raw = await fs.readFile(filePath, 'utf8');
      const className = extractClass(raw);

      if (!className) {
        continue;
      }

      const methods = extractMethods(raw);
      const relativeFile = path.relative(projectRoot, filePath).replaceAll(path.sep, '/');
      const layer = detectLayer(path.relative(projectRoot, filePath));

      if (shouldExcludeClass(layer, className, relativeFile)) {
        continue;
      }

      entries.push({
        module: moduleName,
        layer,
        className,
        description: extractClassDescription(raw, layer, className),
        file: relativeFile,
        methods,
      });
    }
  }

  entries.sort((a, b) => {
    return (
      a.module.localeCompare(b.module) ||
      a.layer.localeCompare(b.layer) ||
      a.className.localeCompare(b.className)
    );
  });

  const payload = {
    generatedAt: new Date().toISOString(),
    totalClasses: entries.length,
    entries,
  };

  await fs.mkdir(path.dirname(outputPath), {recursive: true});
  await fs.writeFile(outputPath, `${JSON.stringify(payload, null, 2)}\n`, 'utf8');

  process.stdout.write(`Generated API reference index: ${outputPath}\n`);
  process.stdout.write(`Classes indexed: ${entries.length}\n`);
}

build().catch((error) => {
  process.stderr.write(`${error.stack || error.message}\n`);
  process.exit(1);
});
