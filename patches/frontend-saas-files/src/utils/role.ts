/** Map API `utype` to frontend route prefix. */
export function routePrefixForUtype(utype: string): string | null {
  const normalized = (utype || "").toLowerCase();
  switch (normalized) {
    case "sadmin":
      return "admin";
    case "cadmin":
      return "manager";
    case "agent":
      return "agent";
    case "accountdepartment":
    case "accountant":
      return "accountdepartment";
    default:
      return null;
  }
}

export function storageRoleFromUtype(utype: string): string {
  const normalized = (utype || "").toLowerCase();
  if (normalized === "accountant") {
    return "accountdepartment";
  }
  return utype;
}
