with open('scratch/generate_master_user_manual.py', 'r', encoding='utf-8') as f:
    lines = f.readlines()

new_lines = []
for line in lines:
    if 'allowing flexible role-based rollout.' in line and not line.strip().endswith('")'):
        new_lines.append('    dh.add_bullet(doc, "Administrative Control: Super Administrators can enable or disable Email OTP on a per-user basis via Staff User Management (/admin/users/edit/:id), allowing flexible role-based rollout.")\n')
    elif 'heightened security compliance is mandated.")")' in line:
        new_lines.append('    dh.add_bullet(doc, "Global Security Setting (Default OFF): In System Settings (/admin/settings under Section 6: Administrative Security), a global toggle allows Super Administrators to turn Two-Step Verification ON or OFF system-wide. By default, it is configured to OFF to maintain fast, frictionless workflow for day-to-day recruiting tasks, while allowing instant activation whenever heightened security compliance is mandated.")\n')
    else:
        new_lines.append(line)

with open('scratch/generate_master_user_manual.py', 'w', encoding='utf-8') as f:
    f.writelines(new_lines)

print("Fixed syntax in generate_master_user_manual.py")

