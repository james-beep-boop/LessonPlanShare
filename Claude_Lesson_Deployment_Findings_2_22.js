const fs = require("fs");
const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  Header, Footer, AlignmentType, HeadingLevel, BorderStyle, WidthType,
  ShadingType, PageNumber, LevelFormat
} = require("docx");

// ── Reusable helpers ──
const border = { style: BorderStyle.SINGLE, size: 1, color: "CCCCCC" };
const borders = { top: border, bottom: border, left: border, right: border };
const cellMargins = { top: 80, bottom: 80, left: 120, right: 120 };

function headerCell(text, width) {
  return new TableCell({
    borders,
    width: { size: width, type: WidthType.DXA },
    shading: { fill: "2B579A", type: ShadingType.CLEAR },
    margins: cellMargins,
    verticalAlign: "center",
    children: [new Paragraph({ children: [new TextRun({ text, bold: true, color: "FFFFFF", font: "Arial", size: 20 })] })]
  });
}

function dataCell(text, width, opts = {}) {
  return new TableCell({
    borders,
    width: { size: width, type: WidthType.DXA },
    shading: opts.shading ? { fill: opts.shading, type: ShadingType.CLEAR } : undefined,
    margins: cellMargins,
    children: [new Paragraph({ children: [new TextRun({ text, font: "Arial", size: 20, ...opts.run })] })]
  });
}

function statusCell(text, width) {
  const isPass = text.toLowerCase().includes("pass") || text.toLowerCase().includes("success") || text.toLowerCase().includes("work");
  const isFail = text.toLowerCase().includes("fail") || text.toLowerCase().includes("not found") || text.toLowerCase().includes("error");
  const color = isPass ? "1B7A2B" : isFail ? "C4314B" : "333333";
  return new TableCell({
    borders,
    width: { size: width, type: WidthType.DXA },
    margins: cellMargins,
    children: [new Paragraph({ children: [new TextRun({ text, font: "Arial", size: 20, bold: true, color })] })]
  });
}

// ── Build document ──
const doc = new Document({
  styles: {
    default: { document: { run: { font: "Arial", size: 22 } } },
    paragraphStyles: [
      { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 32, bold: true, font: "Arial", color: "2B579A" },
        paragraph: { spacing: { before: 360, after: 200 }, outlineLevel: 0 } },
      { id: "Heading2", name: "Heading 2", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 26, bold: true, font: "Arial", color: "2B579A" },
        paragraph: { spacing: { before: 280, after: 160 }, outlineLevel: 1 } },
      { id: "Heading3", name: "Heading 3", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 22, bold: true, font: "Arial", color: "333333" },
        paragraph: { spacing: { before: 200, after: 120 }, outlineLevel: 2 } },
    ]
  },
  numbering: {
    config: [
      { reference: "bullets", levels: [
        { level: 0, format: LevelFormat.BULLET, text: "\u2022", alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 720, hanging: 360 } } } },
        { level: 1, format: LevelFormat.BULLET, text: "\u2013", alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 1440, hanging: 360 } } } }
      ]},
      { reference: "numbers", levels: [
        { level: 0, format: LevelFormat.DECIMAL, text: "%1.", alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 720, hanging: 360 } } } }
      ]},
      { reference: "numbers2", levels: [
        { level: 0, format: LevelFormat.DECIMAL, text: "%1.", alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 720, hanging: 360 } } } }
      ]},
      { reference: "numbers3", levels: [
        { level: 0, format: LevelFormat.DECIMAL, text: "%1.", alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 720, hanging: 360 } } } }
      ]},
    ]
  },
  sections: [{
    properties: {
      page: {
        size: { width: 12240, height: 15840 },
        margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 }
      }
    },
    headers: {
      default: new Header({
        children: [new Paragraph({
          border: { bottom: { style: BorderStyle.SINGLE, size: 6, color: "2B579A", space: 1 } },
          spacing: { after: 120 },
          children: [
            new TextRun({ text: "ARES Education Lesson Plan Archive", font: "Arial", size: 18, color: "2B579A", bold: true }),
            new TextRun({ text: "  |  Deployment Findings", font: "Arial", size: 18, color: "888888" }),
          ]
        })]
      })
    },
    footers: {
      default: new Footer({
        children: [new Paragraph({
          border: { top: { style: BorderStyle.SINGLE, size: 4, color: "CCCCCC", space: 4 } },
          alignment: AlignmentType.CENTER,
          children: [
            new TextRun({ text: "Page ", font: "Arial", size: 16, color: "888888" }),
            new TextRun({ children: [PageNumber.CURRENT], font: "Arial", size: 16, color: "888888" }),
          ]
        })]
      })
    },
    children: [

      // ── TITLE PAGE ──
      new Paragraph({ spacing: { before: 2400 }, alignment: AlignmentType.CENTER, children: [] }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { after: 200 },
        children: [new TextRun({ text: "ARES Education Lesson Plan Archive", font: "Arial", size: 44, bold: true, color: "2B579A" })]
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { after: 600 },
        children: [new TextRun({ text: "DreamHost Deployment Findings", font: "Arial", size: 32, color: "555555" })]
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        border: { top: { style: BorderStyle.SINGLE, size: 4, color: "2B579A", space: 8 },
                  bottom: { style: BorderStyle.SINGLE, size: 4, color: "2B579A", space: 8 } },
        spacing: { before: 200, after: 200 },
        children: [
          new TextRun({ text: "Date: February 22, 2026", font: "Arial", size: 22, color: "333333" }),
          new TextRun({ text: "\nPrepared by: Claude (AI Assistant) in collaboration with David", font: "Arial", size: 22, color: "333333", break: 1 }),
          new TextRun({ text: "\nDomain: www.sheql.com", font: "Arial", size: 22, color: "333333", break: 1 }),
          new TextRun({ text: "\nHost: DreamHost Shared Hosting", font: "Arial", size: 22, color: "333333", break: 1 }),
        ]
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 400 },
        children: [new TextRun({ text: "Status: Deployment Blocked", font: "Arial", size: 26, bold: true, color: "C4314B" })]
      }),

      // ── 1. EXECUTIVE SUMMARY ──
      new Paragraph({ pageBreakBefore: true, heading: HeadingLevel.HEADING_1, children: [new TextRun("1. Executive Summary")] }),
      new Paragraph({ spacing: { after: 160 }, children: [
        new TextRun("The ARES Education Lesson Plan Archive is a Laravel 12 web application designed for high school teachers to share lesson plan documents. The application code is complete and functional. Deployment to DreamHost shared hosting (www.sheql.com) was attempted on February 22, 2026. While all server-side setup steps succeeded (Laravel installation, Breeze authentication, custom file overlay, Composer, database connection, migrations), the site returns a "),
        new TextRun({ text: "\"Site Not Found\"", bold: true }),
        new TextRun(" error in the browser. The root cause appears to be a DreamHost Apache virtual host configuration issue that prevents the web server from serving files from the expected directory."),
      ]}),

      // ── 2. ENVIRONMENT ──
      new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun("2. Environment Details")] }),

      new Table({
        width: { size: 9360, type: WidthType.DXA },
        columnWidths: [3120, 6240],
        rows: [
          new TableRow({ children: [headerCell("Component", 3120), headerCell("Value", 6240)] }),
          new TableRow({ children: [dataCell("Hosting Provider", 3120, { run: { bold: true }}), dataCell("DreamHost Shared Hosting", 6240)] }),
          new TableRow({ children: [dataCell("SSH User", 3120, { run: { bold: true }}), dataCell("david_sheql@sheql.com", 6240)] }),
          new TableRow({ children: [dataCell("Server Identifier", 3120, { run: { bold: true }}), dataCell("iad1-shared-b8-43", 6240)] }),
          new TableRow({ children: [dataCell("Domain", 3120, { run: { bold: true }}), dataCell("sheql.com / www.sheql.com", 6240)] }),
          new TableRow({ children: [dataCell("Web Directory (Panel)", 3120, { run: { bold: true }}), dataCell("/home/david_sheql/sheql.com", 6240)] }),
          new TableRow({ children: [dataCell("Database Host", 3120, { run: { bold: true }}), dataCell("mysql.sheql.com (created during session)", 6240)] }),
          new TableRow({ children: [dataCell("Database Name", 3120, { run: { bold: true }}), dataCell("sheql_lessons", 6240)] }),
          new TableRow({ children: [dataCell("Database User", 3120, { run: { bold: true }}), dataCell("sheql_dbuser", 6240)] }),
          new TableRow({ children: [dataCell("PHP Version", 3120, { run: { bold: true }}), dataCell("8.4 (DreamHost-managed)", 6240)] }),
          new TableRow({ children: [dataCell("Framework", 3120, { run: { bold: true }}), dataCell("Laravel 12 + Breeze (Blade)", 6240)] }),
          new TableRow({ children: [dataCell("GitHub Repository", 3120, { run: { bold: true }}), dataCell("github.com/james-beep-boop/LessonPlanShare (public)", 6240)] }),
        ]
      }),

      // ── 3. DEPLOYMENT STEPS ──
      new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun("3. Deployment Steps Attempted")] }),
      new Paragraph({ spacing: { after: 120 }, children: [
        new TextRun("The deployment was performed manually via SSH after the automated DEPLOY_DREAMHOST.sh script encountered issues with Composer path expansion. Each step below was executed individually."),
      ]}),

      new Paragraph({ heading: HeadingLevel.HEADING_2, children: [new TextRun("3.1 Steps That Succeeded")] }),

      new Table({
        width: { size: 9360, type: WidthType.DXA },
        columnWidths: [600, 4560, 2100, 2100],
        rows: [
          new TableRow({ children: [
            headerCell("#", 600), headerCell("Step", 4560), headerCell("Command/Action", 2100), headerCell("Result", 2100)
          ]}),
          new TableRow({ children: [
            dataCell("1", 600), dataCell("Install Composer", 4560),
            dataCell("php composer-setup.php", 2100), statusCell("Success", 2100)
          ]}),
          new TableRow({ children: [
            dataCell("2", 600), dataCell("Create Laravel 12 project", 4560),
            dataCell("composer create-project", 2100), statusCell("Success", 2100)
          ]}),
          new TableRow({ children: [
            dataCell("3", 600), dataCell("Install Laravel Breeze", 4560),
            dataCell("composer require + artisan breeze:install", 2100), statusCell("Success", 2100)
          ]}),
          new TableRow({ children: [
            dataCell("4", 600), dataCell("Clone GitHub repo + overlay custom files", 4560),
            dataCell("git clone + cp -r", 2100), statusCell("Success", 2100)
          ]}),
          new TableRow({ children: [
            dataCell("5", 600), dataCell("Configure .env (app key, DB, mail settings)", 4560),
            dataCell("cp .env.example + key:generate + sed", 2100), statusCell("Success", 2100)
          ]}),
          new TableRow({ children: [
            dataCell("6", 600), dataCell("Create MySQL hostname (mysql.sheql.com)", 4560),
            dataCell("DreamHost Panel", 2100), statusCell("Success", 2100)
          ]}),
          new TableRow({ children: [
            dataCell("7", 600), dataCell("Database connection test", 4560),
            dataCell("php artisan migrate:status", 2100), statusCell("Success (table not found = connected)", 2100)
          ]}),
          new TableRow({ children: [
            dataCell("8", 600), dataCell("Run database migrations", 4560),
            dataCell("php artisan migrate --force", 2100), statusCell("Success", 2100)
          ]}),
          new TableRow({ children: [
            dataCell("9", 600), dataCell("Create storage symlink", 4560),
            dataCell("php artisan storage:link", 2100), statusCell("Success", 2100)
          ]}),
          new TableRow({ children: [
            dataCell("10", 600), dataCell("Set directory permissions", 4560),
            dataCell("chmod -R 775", 2100), statusCell("Success", 2100)
          ]}),
          new TableRow({ children: [
            dataCell("11", 600), dataCell("Cache config, routes, views", 4560),
            dataCell("php artisan config/route/view:cache", 2100), statusCell("Success", 2100)
          ]}),
          new TableRow({ children: [
            dataCell("12", 600), dataCell("Verify Laravel routes registered", 4560),
            dataCell("php artisan route:list", 2100), statusCell("Success (all routes visible)", 2100)
          ]}),
        ]
      }),

      new Paragraph({ heading: HeadingLevel.HEADING_2, children: [new TextRun("3.2 The Blocking Issue")] }),
      new Paragraph({ spacing: { after: 160 }, children: [
        new TextRun("Despite all server-side steps completing successfully, visiting the site in a browser (https://sheql.com, https://www.sheql.com, http://sheql.com, or http://www.sheql.com) returns a "),
        new TextRun({ text: "\"Site Not Found\"", bold: true }),
        new TextRun(" error. This error comes from DreamHost, indicating that Apache is unable to find a virtual host configuration that matches the incoming request."),
      ]}),

      // ── 4. TROUBLESHOOTING ──
      new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun("4. Troubleshooting Attempts")] }),
      new Paragraph({ spacing: { after: 120 }, children: [
        new TextRun("Multiple approaches were tried to resolve the web serving issue. None succeeded."),
      ]}),

      new Paragraph({ heading: HeadingLevel.HEADING_2, children: [new TextRun("4.1 Approach 1: Symlink")] }),
      new Paragraph({ spacing: { after: 80 }, children: [
        new TextRun({ text: "Rationale: ", bold: true }),
        new TextRun("DreamHost serves from ~/sheql.com. Laravel needs to serve from its public/ directory. A symlink would make ~/sheql.com point to ~/LessonPlanShare/public."),
      ]}),
      new Paragraph({ spacing: { after: 80 }, children: [
        new TextRun({ text: "Command: ", bold: true }),
        new TextRun({ text: "ln -sf ~/LessonPlanShare/public ~/sheql.com", font: "Courier New", size: 20 }),
      ]}),
      new Paragraph({ spacing: { after: 80 }, children: [
        new TextRun({ text: "Verification: ", bold: true }),
        new TextRun("ls -la confirmed the symlink existed and pointed to the correct target. ls ~/sheql.com/index.php confirmed index.php was reachable through the symlink."),
      ]}),
      new Paragraph({ spacing: { after: 160 }, children: [
        new TextRun({ text: "Result: Site Not Found. ", bold: true, color: "C4314B" }),
        new TextRun("DreamHost Apache likely does not follow symlinks for the web root directory, or the FollowSymlinks directive is disabled for home directories."),
      ]}),

      new Paragraph({ heading: HeadingLevel.HEADING_2, children: [new TextRun("4.2 Approach 2: Real Directory with Modified index.php")] }),
      new Paragraph({ spacing: { after: 80 }, children: [
        new TextRun({ text: "Rationale: ", bold: true }),
        new TextRun("If symlinks are not followed, copy the public/ files into a real ~/sheql.com directory and modify index.php to bootstrap Laravel from ~/LessonPlanShare."),
      ]}),
      new Paragraph({ spacing: { after: 80 }, children: [
        new TextRun({ text: "Commands: ", bold: true }),
        new TextRun("Removed symlink, created real directory, copied all public/ files, created storage symlink inside it, and edited index.php to use absolute paths pointing to ~/LessonPlanShare/vendor/autoload.php and ~/LessonPlanShare/bootstrap/app.php."),
      ]}),
      new Paragraph({ spacing: { after: 160 }, children: [
        new TextRun({ text: "Result: Site Not Found. ", bold: true, color: "C4314B" }),
        new TextRun("Even with a real directory and proper index.php, the site was not served."),
      ]}),

      new Paragraph({ heading: HeadingLevel.HEADING_2, children: [new TextRun("4.3 Approach 3: Plain PHP Test File")] }),
      new Paragraph({ spacing: { after: 80 }, children: [
        new TextRun({ text: "Rationale: ", bold: true }),
        new TextRun("To isolate whether the issue is Laravel-specific or Apache-level, we created the simplest possible PHP file."),
      ]}),
      new Paragraph({ spacing: { after: 80 }, children: [
        new TextRun({ text: "Command: ", bold: true }),
        new TextRun({ text: "echo '<?php echo \"PHP works\";' > ~/sheql.com/test.php", font: "Courier New", size: 20 }),
      ]}),
      new Paragraph({ spacing: { after: 80 }, children: [
        new TextRun({ text: "Test URL: ", bold: true }),
        new TextRun("http://sheql.com/test.php"),
      ]}),
      new Paragraph({ spacing: { after: 160 }, children: [
        new TextRun({ text: "Result: Site Not Found. ", bold: true, color: "C4314B" }),
        new TextRun("This is the critical finding. A plain PHP file with no dependencies, placed directly in the DreamHost-configured web directory, is not served. This confirms the problem is at the Apache/virtual host level, not in Laravel or our code."),
      ]}),

      new Paragraph({ heading: HeadingLevel.HEADING_2, children: [new TextRun("4.4 Approach 4: Alternate Directory Name (www.sheql.com)")] }),
      new Paragraph({ spacing: { after: 80 }, children: [
        new TextRun({ text: "Rationale: ", bold: true }),
        new TextRun("DreamHost might use ~/www.sheql.com instead of ~/sheql.com if the domain is configured with the www prefix."),
      ]}),
      new Paragraph({ spacing: { after: 80 }, children: [
        new TextRun({ text: "Command: ", bold: true }),
        new TextRun("Created ~/www.sheql.com directory with test.php inside."),
      ]}),
      new Paragraph({ spacing: { after: 160 }, children: [
        new TextRun({ text: "Result: Site Not Found. ", bold: true, color: "C4314B" }),
        new TextRun("Neither directory name worked."),
      ]}),

      // ── 5. DIAGNOSTIC FINDINGS ──
      new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun("5. Diagnostic Evidence")] }),

      new Paragraph({ heading: HeadingLevel.HEADING_2, children: [new TextRun("5.1 Home Directory Contents")] }),
      new Paragraph({ spacing: { after: 80 }, children: [
        new TextRun("The home directory listing (ls -la ~) reveals the following relevant items:"),
      ]}),
      new Paragraph({ numbering: { reference: "bullets", level: 0 }, children: [
        new TextRun({ text: "sheql.com", bold: true }),
        new TextRun(" \u2014 real directory (formerly a symlink; now contains public/ files + modified index.php)"),
      ]}),
      new Paragraph({ numbering: { reference: "bullets", level: 0 }, children: [
        new TextRun({ text: "sheql.com_app", bold: true }),
        new TextRun(" \u2014 symlink pointing to /home/david_sheql/sheql.com_core/public"),
      ]}),
      new Paragraph({ numbering: { reference: "bullets", level: 0 }, children: [
        new TextRun({ text: "LessonPlanShare", bold: true }),
        new TextRun(" \u2014 the full Laravel project directory"),
      ]}),
      new Paragraph({ numbering: { reference: "bullets", level: 0 }, spacing: { after: 120 }, children: [
        new TextRun({ text: "logs/sheql.com/", bold: true }),
        new TextRun(" \u2014 contains http/ and https/ subdirectories (confirms domain is registered in Apache)"),
      ]}),

      new Paragraph({ heading: HeadingLevel.HEADING_2, children: [new TextRun("5.2 The sheql.com_app Mystery")] }),
      new Paragraph({ spacing: { after: 160 }, children: [
        new TextRun("The existence of "),
        new TextRun({ text: "sheql.com_app", bold: true }),
        new TextRun(" (a symlink to sheql.com_core/public) is significant. This naming pattern is characteristic of DreamHost Passenger application configurations. When a domain is configured with Passenger (for Ruby, Node.js, or Python apps), DreamHost creates a _app and _core directory pair. Apache may be configured to serve from sheql.com_app rather than sheql.com, but sheql.com_core does not exist on the filesystem, which means the symlink target is broken. This could be the fundamental cause of the \"Site Not Found\" error."),
      ]}),

      new Paragraph({ heading: HeadingLevel.HEADING_2, children: [new TextRun("5.3 Error Logs")] }),
      new Paragraph({ spacing: { after: 160 }, children: [
        new TextRun("The error log files at ~/logs/sheql.com/http/error.log and ~/logs/sheql.com/https/error.log were both empty (file not found). This suggests either logging is not enabled for this virtual host, or Apache is rejecting the request before it reaches the stage where errors would be logged (i.e., the virtual host itself is misconfigured)."),
      ]}),

      new Paragraph({ heading: HeadingLevel.HEADING_2, children: [new TextRun("5.4 SSL Certificate")] }),
      new Paragraph({ spacing: { after: 160 }, children: [
        new TextRun("Visiting the HTTPS version of the site initially showed a \"Site Not Private\" warning (SSL certificate issue). This was bypassed, but the site still returned \"Site Not Found.\" The HTTP version also returned \"Site Not Found.\" This indicates the issue is not SSL-related."),
      ]}),

      // ── 6. THINGS THAT WORK ──
      new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun("6. What Is Confirmed Working")] }),

      new Paragraph({ numbering: { reference: "numbers", level: 0 }, children: [
        new TextRun({ text: "Laravel Installation: ", bold: true }),
        new TextRun("Fresh Laravel 12 project created successfully via Composer."),
      ]}),
      new Paragraph({ numbering: { reference: "numbers", level: 0 }, children: [
        new TextRun({ text: "Breeze Authentication: ", bold: true }),
        new TextRun("Installed and configured. npm errors were expected and harmless (CDN-based frontend)."),
      ]}),
      new Paragraph({ numbering: { reference: "numbers", level: 0 }, children: [
        new TextRun({ text: "Custom File Overlay: ", bold: true }),
        new TextRun("All application files successfully copied from GitHub repo to Laravel project."),
      ]}),
      new Paragraph({ numbering: { reference: "numbers", level: 0 }, children: [
        new TextRun({ text: "Database Connection: ", bold: true }),
        new TextRun("MySQL hostname (mysql.sheql.com) created and responding. artisan migrate:status confirms connectivity."),
      ]}),
      new Paragraph({ numbering: { reference: "numbers", level: 0 }, children: [
        new TextRun({ text: "Migrations: ", bold: true }),
        new TextRun("All database tables created (lesson_plans, votes, users, plus Laravel system tables)."),
      ]}),
      new Paragraph({ numbering: { reference: "numbers", level: 0 }, children: [
        new TextRun({ text: "Route Registration: ", bold: true }),
        new TextRun("php artisan route:list shows all expected routes (dashboard, stats, lesson-plans CRUD, preview, download, auth)."),
      ]}),
      new Paragraph({ numbering: { reference: "numbers", level: 0 }, children: [
        new TextRun({ text: "File Permissions: ", bold: true }),
        new TextRun("storage/ and bootstrap/cache/ set to 775."),
      ]}),
      new Paragraph({ numbering: { reference: "numbers", level: 0 }, spacing: { after: 120 }, children: [
        new TextRun({ text: "Production Caches: ", bold: true }),
        new TextRun("Config, route, and view caches built successfully."),
      ]}),

      // ── 7. ISSUES ENCOUNTERED ──
      new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun("7. Other Issues Encountered During Deployment")] }),

      new Paragraph({ heading: HeadingLevel.HEADING_3, children: [new TextRun("7.1 Private GitHub Repository")] }),
      new Paragraph({ spacing: { after: 120 }, children: [
        new TextRun("The repository was initially private, causing curl and the deployment script to receive 404 errors when fetching raw files. "),
        new TextRun({ text: "Resolution: ", bold: true }),
        new TextRun("Repository was changed to public. No sensitive credentials are stored in the repo (.env is gitignored)."),
      ]}),

      new Paragraph({ heading: HeadingLevel.HEADING_3, children: [new TextRun("7.2 Composer Path Expansion (~ vs $HOME)")] }),
      new Paragraph({ spacing: { after: 120 }, children: [
        new TextRun("The DEPLOY_DREAMHOST.sh script used ~/composer.phar in a variable assignment, but the tilde (~) does not expand inside double quotes in bash. This caused \"Could not open input file: ~/composer.phar\" errors. "),
        new TextRun({ text: "Resolution: ", bold: true }),
        new TextRun("Changed to $HOME/composer.phar. However, since the script was updated locally but not re-pushed before re-running, the fix was applied manually on the server."),
      ]}),

      new Paragraph({ heading: HeadingLevel.HEADING_3, children: [new TextRun("7.3 MySQL Hostname Not Created")] }),
      new Paragraph({ spacing: { after: 120 }, children: [
        new TextRun("DreamHost requires MySQL hostnames to be explicitly created in the panel. mysql.sheql.com did not exist initially, causing \"php_network_getaddresses: getaddrinfo failed\" errors. "),
        new TextRun({ text: "Resolution: ", bold: true }),
        new TextRun("Created mysql.sheql.com in the DreamHost panel under MySQL Databases. It propagated within minutes."),
      ]}),

      new Paragraph({ heading: HeadingLevel.HEADING_3, children: [new TextRun("7.4 Repo Structure Mismatch")] }),
      new Paragraph({ spacing: { after: 120 }, children: [
        new TextRun("The deployment script assumed custom files were in a LessonPlanShare/ subfolder within the repo, but they were at the repo root. This caused all cp -r overlay commands to copy from wrong paths. "),
        new TextRun({ text: "Resolution: ", bold: true }),
        new TextRun("Updated both DEPLOY_DREAMHOST.sh and UPDATE_SITE.sh to use correct paths (e.g., ~/LessonPlanCustom/app/* instead of ~/LessonPlanCustom/LessonPlanShare/app/*)."),
      ]}),

      // ── 8. CONCLUSION ──
      new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun("8. Conclusion and Root Cause Analysis")] }),

      new Paragraph({ spacing: { after: 160 }, children: [
        new TextRun({ text: "The application code is not the problem. ", bold: true }),
        new TextRun("Every server-side component works: PHP runs, Composer installs packages, Laravel boots, routes are registered, the database connects, migrations run. The issue is entirely at the DreamHost Apache/virtual host layer."),
      ]}),

      new Paragraph({ spacing: { after: 120 }, children: [
        new TextRun({ text: "Most likely cause: ", bold: true }),
        new TextRun("The domain sheql.com was previously configured (or is currently configured) as a Passenger application in the DreamHost panel. The existence of the sheql.com_app symlink (pointing to the nonexistent sheql.com_core/public) strongly suggests this. When Passenger is enabled, Apache does not serve from ~/sheql.com directly \u2014 it routes requests through the Passenger application server, which looks for sheql.com_core. Since that directory does not exist, the site returns \"Not Found.\""),
      ]}),

      new Paragraph({ spacing: { after: 160 }, children: [
        new TextRun({ text: "Alternative explanation: ", bold: true }),
        new TextRun("The domain may have a DNS or virtual host configuration issue in the DreamHost panel that prevents Apache from matching incoming requests to the correct directory. The empty error logs suggest requests are being rejected before reaching the per-site logging stage."),
      ]}),

      // ── 9. RECOMMENDED NEXT STEPS ──
      new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun("9. Recommended Next Steps")] }),

      new Paragraph({ numbering: { reference: "numbers2", level: 0 }, spacing: { after: 80 }, children: [
        new TextRun({ text: "Check for Passenger Configuration: ", bold: true }),
        new TextRun("In the DreamHost panel, go to Manage Websites and look for any Passenger, Ruby, Node.js, or Python app configuration on sheql.com. If found, disable it and switch to standard PHP hosting. This is the single most likely fix."),
      ]}),
      new Paragraph({ numbering: { reference: "numbers2", level: 0 }, spacing: { after: 80 }, children: [
        new TextRun({ text: "Remove the sheql.com_app Symlink: ", bold: true }),
        new TextRun("Run: rm ~/sheql.com_app \u2014 This removes the stale Passenger artifact. It points to a nonexistent directory and may be confusing Apache."),
      ]}),
      new Paragraph({ numbering: { reference: "numbers2", level: 0 }, spacing: { after: 80 }, children: [
        new TextRun({ text: "Delete and Re-add the Domain: ", bold: true }),
        new TextRun("In the DreamHost panel, remove sheql.com from hosting entirely, then re-add it as a standard PHP-hosted domain. This will reset the Apache virtual host configuration. After re-adding, restore the symlink: ln -sf ~/LessonPlanShare/public ~/sheql.com"),
      ]}),
      new Paragraph({ numbering: { reference: "numbers2", level: 0 }, spacing: { after: 80 }, children: [
        new TextRun({ text: "Contact DreamHost Support: ", bold: true }),
        new TextRun("Ask them to verify the virtual host configuration for sheql.com. Specifically ask: (a) Is the domain configured for Passenger/proxy? (b) Is Apache configured to serve from /home/david_sheql/sheql.com? (c) Is FollowSymlinks enabled for the home directory? They can see the Apache config that is not accessible to shared hosting users."),
      ]}),
      new Paragraph({ numbering: { reference: "numbers2", level: 0 }, spacing: { after: 80 }, children: [
        new TextRun({ text: "Check DNS Resolution: ", bold: true }),
        new TextRun("Run: nslookup sheql.com and nslookup www.sheql.com to verify the domain points to DreamHost servers. If DNS is pointing elsewhere, the DreamHost server would never receive the request."),
      ]}),
      new Paragraph({ numbering: { reference: "numbers2", level: 0 }, spacing: { after: 80 }, children: [
        new TextRun({ text: "Try a Completely New Domain: ", bold: true }),
        new TextRun("As a diagnostic step, add a temporary subdomain (e.g., test.sheql.com) in the DreamHost panel as a standard PHP domain, point it to ~/LessonPlanShare/public, and see if that one serves. If it does, the issue is specific to the sheql.com domain configuration."),
      ]}),

      // ── 10. CURRENT STATE ──
      new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun("10. Current Server State")] }),
      new Paragraph({ spacing: { after: 120 }, children: [
        new TextRun("As of the end of this session, the DreamHost server has the following layout:"),
      ]}),

      new Paragraph({ numbering: { reference: "bullets", level: 0 }, children: [
        new TextRun({ text: "~/LessonPlanShare/", bold: true }),
        new TextRun(" \u2014 Complete Laravel 12 project with all custom files, configured .env, migrated database, cached config/routes/views. Fully functional if Apache can reach it."),
      ]}),
      new Paragraph({ numbering: { reference: "bullets", level: 0 }, children: [
        new TextRun({ text: "~/sheql.com/", bold: true }),
        new TextRun(" \u2014 Real directory containing copies of public/ files plus a modified index.php that bootstraps from ~/LessonPlanShare. Also contains test.php."),
      ]}),
      new Paragraph({ numbering: { reference: "bullets", level: 0 }, children: [
        new TextRun({ text: "~/www.sheql.com/", bold: true }),
        new TextRun(" \u2014 Real directory with test.php (created during troubleshooting)."),
      ]}),
      new Paragraph({ numbering: { reference: "bullets", level: 0 }, children: [
        new TextRun({ text: "~/sheql.com_app", bold: true }),
        new TextRun(" \u2014 Stale symlink to nonexistent sheql.com_core/public. Should be removed."),
      ]}),
      new Paragraph({ numbering: { reference: "bullets", level: 0 }, children: [
        new TextRun({ text: "~/composer.phar", bold: true }),
        new TextRun(" \u2014 Composer binary, working correctly."),
      ]}),
      new Paragraph({ numbering: { reference: "bullets", level: 0 }, spacing: { after: 120 }, children: [
        new TextRun({ text: "~/.bashrc", bold: true }),
        new TextRun(" \u2014 Has composer alias added."),
      ]}),

      new Paragraph({ spacing: { after: 160 }, children: [
        new TextRun({ text: "Once the Apache/virtual host issue is resolved, the site should work immediately ", bold: true }),
        new TextRun("with either the symlink approach (preferred for clean updates) or the real-directory approach. No additional code changes are needed."),
      ]}),

    ]
  }]
});

// ── Write file ──
Packer.toBuffer(doc).then(buffer => {
  fs.writeFileSync("/sessions/pensive-modest-dirac/mnt/LessonPlanShare/Claude_Lesson_Deployment_Findings_2_22.docx", buffer);
  console.log("Document created successfully.");
});
