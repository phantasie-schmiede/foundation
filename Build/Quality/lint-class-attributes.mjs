#!/usr/bin/env node

import {glob, readFile, writeFile} from 'node:fs/promises';
import path from 'node:path';

const bootstrapBreakpointOrder = new Map([
    [
        'xs',
        0
    ],
    [
        'sm',
        1
    ],
    [
        'md',
        2
    ],
    [
        'lg',
        3
    ],
    [
        'xl',
        4
    ],
    [
        'xxl',
        5
    ],
]);
const classAttributePattern = /(\bclass\s*=\s*)(["'])([^"']*)(\2)/g;
const dryRunMode = process.argv.includes('--dry-run');
const fluidExpressionPattern = /[{}]/;
const lintErrors = [];
const repositoryRoot = process.cwd();
const targets = [
    'Resources/Private/**/*.html',
];

const compareClassNames = (left, right) => {
    // Special rules for bootstrap breakpoints
    const leftBreakpoint = getBootstrapBreakpointSignature(left);
    const rightBreakpoint = getBootstrapBreakpointSignature(right);

    if (
        leftBreakpoint &&
        rightBreakpoint &&
        leftBreakpoint.signature === rightBreakpoint.signature &&
        leftBreakpoint.order !== rightBreakpoint.order
    ) {
        return leftBreakpoint.order - rightBreakpoint.order;
    }

    if (leftBreakpoint && isBaseVariantForSignature(right, leftBreakpoint.signature)) {
        return 1;
    }

    if (rightBreakpoint && isBaseVariantForSignature(left, rightBreakpoint.signature)) {
        return -1;
    }

    // Special rules for BEM structured classes
    const leftModifierBase = getBemModifierBase(left);
    const rightModifierBase = getBemModifierBase(right);

    if (leftModifierBase && leftModifierBase === right) {
        return 1;
    }

    if (rightModifierBase && rightModifierBase === left) {
        return -1;
    }

    if (leftModifierBase && isBemElementOf(right, leftModifierBase)) {
        return -1;
    }

    if (rightModifierBase && isBemElementOf(left, rightModifierBase)) {
        return 1;
    }

    // Default sorting
    return left.localeCompare(right, 'en', {
        numeric: true,
        sensitivity: 'base',
    });
};

const getBemModifierBase = (className) => {
    const modifierIndex = className.indexOf('--');
    return modifierIndex === -1 ? null : className.slice(0, modifierIndex);
};

const getBootstrapBreakpointSignature = (className) => {
    const match = className.match(/^(.*?)-(xs|sm|md|lg|xl|xxl)(-.+)?$/);

    if (!match) {
        return null;
    }

    return {
        signature: `${match[1]}-`,
        order: bootstrapBreakpointOrder.get(match[2]) ?? Number.MAX_SAFE_INTEGER,
    };
};

const isBaseVariantForSignature = (className, signature) => {
    return !getBootstrapBreakpointSignature(className) && className.startsWith(signature);
};

const isBemElementOf = (className, blockOrElementBase) => {
    return className.startsWith(`${blockOrElementBase}__`);
};

const lintFile = async (filePath) => {
    let updatedContent = await readFile(filePath, 'utf8');
    let foundIssue = false;

    updatedContent = updatedContent.replace(
        classAttributePattern,
        (match, prefix, quote, classValue) => {
            // Don't process attributes with fluid syntax.
            if (fluidExpressionPattern.test(classValue)) {
                return match;
            }

            const normalized = normalizeClassAttribute(classValue);

            if (!normalized.changed) {
                return match;
            }

            foundIssue = true;

            return `${prefix}${quote}${normalized.sorted}${quote}`;
        },
    );

    if (!foundIssue) {
        return;
    }

    const relativePath = path.relative(repositoryRoot, filePath);

    if (!dryRunMode) {
        await writeFile(filePath, updatedContent, 'utf8');
        console.log(`Fixed class order in ${relativePath}`);
        return;
    }

    lintErrors.push(relativePath);
};

const normalizeClassAttribute = (value) => {
    const classes = value.trim().split(/\s+/).filter(Boolean);
    const uniqueClasses = [...new Set(classes)];
    const sortedClasses = [...uniqueClasses].sort(compareClassNames);

    return {
        changed: classes.join(' ') !== sortedClasses.join(' '),
        sorted: sortedClasses.join(' '),
    };
};

const run = async () => {
    const files = [];

    for (const target of targets) {
        for await (const file of glob(target, {cwd: repositoryRoot})) {
            files.push(file);
        }
    }

    for (const filePath of files) {
        await lintFile(filePath);
    }

    if (0 === lintErrors.length) {
        return;
    }

    console.error('Class attribute values are not sorted alphabetically or have duplicates in:');
    for (const lintError of lintErrors) {
        console.error(`- ${lintError}`);
    }
    console.error('Run `npm run fix:classes` to fix class order.');
    process.exitCode = 1;
};

await run();
