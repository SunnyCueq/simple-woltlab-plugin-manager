const vscode = require("vscode");
const path = require("path");
const fs = require("fs");

/** Pfad zum WoltLab-Entwicklungs-Root (Ordner, in dem tools.sh und tools/ liegen). */
function getToolsRoot() {
  const config = vscode.workspace.getConfiguration("woltlabDevelopment").get("toolsRoot");
  if (config && typeof config === "string" && config.trim() !== "") {
    const p = path.isAbsolute(config) ? config : path.resolve(config);
    const toolsSh = path.join(p, "tools", "tools.sh");
    if (fs.existsSync(toolsSh)) return p;
  }
  const folders = vscode.workspace.workspaceFolders;
  if (folders) {
    for (const folder of folders) {
      const base = folder.uri.fsPath;
      const inBase = path.join(base, "tools", "tools.sh");
      if (fs.existsSync(inBase)) return base;
      const parent = path.dirname(base);
      const inParent = path.join(parent, "tools", "tools.sh");
      if (fs.existsSync(inParent)) return parent;
    }
  }
  return null;
}

/** Führt ein Script im Terminal aus (cwd = toolsRoot). */
function runScript(toolsRoot, scriptPath, args = []) {
  if (!toolsRoot) {
    vscode.window.showErrorMessage(
      "WoltLab Tools-Root nicht gefunden. Bitte Pfad in Einstellungen (woltlabDevelopment.toolsRoot) setzen."
    );
    return;
  }
  const cmd = [scriptPath, ...args].filter(Boolean).join(" ");
  const term = vscode.window.createTerminal({
    name: "WoltLab Tools",
    cwd: toolsRoot,
  });
  term.show();
  term.sendText(cmd);
}

const TOOL_ENTRIES = [
  { id: 1, label: "Build", command: "woltlabBuild.runTool1", script: "./tools/build.sh", args: ["patch"], icon: "run-all" },
  { id: 2, label: "Git Push", command: "woltlabBuild.runTool2", script: "./tools/gitpush.sh", args: [], icon: "git-push" },
  { id: 3, label: "TypeScript", command: "woltlabBuild.runTool3", script: "./tools/typescript.sh", args: [], icon: "file-code" },
  { id: 4, label: "Unpack", command: "woltlabBuild.runTool4", script: "./tools/unpack.sh", args: [], icon: "package" },
  { id: 5, label: "Hilfe & Dokumentation", command: "woltlabBuild.runTool5", script: "./tools/help.sh", args: [], icon: "book" },
  { id: 6, label: "Plugin Validierung", command: "woltlabBuild.runTool6", script: "./tools/validate-plugin.sh", args: [], icon: "check-all" },
  { id: "managerPush", label: "Manager Push (Maintainer)", command: "woltlabBuild.runToolManagerPush", script: "./tools/manager-push.sh", args: [], icon: "git-push" },
  { id: "menu", label: "Tools-Menü", command: "woltlabBuild.runToolsMenu", script: "./tools.sh", args: [], icon: "list-flat" },
];

function activate(context) {
  const runBuild = vscode.commands.registerCommand("woltlabBuild.run", async () => {
    const root = getToolsRoot();
    if (root) {
      runScript(root, "./tools/build.sh", ["patch"]);
    } else {
      const tasks = await vscode.tasks.fetchTasks();
      const buildTask = tasks.find(
        (t) => t.name === "woltlab-build" || t.name === "WoltLab Build (Patch)"
      );
      if (buildTask) {
        await vscode.tasks.executeTask(buildTask);
      } else {
        await vscode.commands.executeCommand("workbench.action.tasks.runTask");
      }
    }
  });
  context.subscriptions.push(runBuild);

  for (const entry of TOOL_ENTRIES) {
    const cmd = entry.command;
    const script = entry.script;
    const args = entry.args;
    const handler = vscode.commands.registerCommand(cmd, () => {
      runScript(getToolsRoot(), script, args);
    });
    context.subscriptions.push(handler);
  }

  class WoltLabToolsTreeProvider {
    getTreeItem(element) {
      return element;
    }
    getChildren() {
      const root = getToolsRoot();
      const showManagerPush = root && fs.existsSync(path.join(root, "tools", "manager-push.sh"));
      const entries = TOOL_ENTRIES.filter((e) => e.id !== "managerPush" || showManagerPush);
      return entries.map((e) => {
        const item = new vscode.TreeItem(e.label, vscode.TreeItemCollapsibleState.None);
        item.command = { command: e.command, title: e.label };
        item.iconPath = new vscode.ThemeIcon(e.icon || "run-all");
        return item;
      });
    }
  }

  context.subscriptions.push(
    vscode.window.registerTreeDataProvider("woltlabBuildView", new WoltLabToolsTreeProvider())
  );
}

function deactivate() {}

module.exports = { activate, deactivate };
