import { route } from 'ziggy-js';
import { NavLink } from './NavLink';

interface MobileNavigationProps {
    isAdmin: boolean;
    isTeacher: boolean;
    isStudent: boolean;
    currentHref: string;
    isOpen: boolean;
}

const isActiveLink = (href: string, currentHref: string) => {
    return currentHref === href || currentHref.startsWith(href + '/');
};

export const MobileNavigation = ({ isAdmin, isTeacher, isStudent, currentHref, isOpen }: MobileNavigationProps) => {
    if (!isOpen) return null;

    return (
        <div className="sm:hidden">
            <div className="pt-2 pb-3 space-y-1 border-t border-gray-200">
                <NavLink
                    href={route('dashboard')}
                    isActive={isActiveLink(route('dashboard'), currentHref)}
                    variant="mobile"
                >
                    Tableau de bord
                </NavLink>

                {isStudent && (
                    <>
                        <NavLink
                            href={route('student.exams.index')}
                            isActive={isActiveLink(route('student.exams.index'), currentHref)}
                            variant="mobile"
                        >
                            Mes Examens
                        </NavLink>
                    </>
                )}

                {isTeacher && (
                    <>
                        <NavLink
                            href={route('teacher.exams.index')}
                            isActive={isActiveLink(route('teacher.exams.index'), currentHref)}
                            variant="mobile"
                        >
                            Mes Examens
                        </NavLink>
                        {/* <NavLink
                            href={route('teacher.results.index')}
                            isActive={isActiveLink(route('teacher.results.index'), currentHref)}
                            variant="mobile"
                        >
                            Résultats
                        </NavLink> */}
                    </>
                )}

                {isAdmin && (
                    <>
                        {/* <NavLink
                            href={route('admin.exams.index')}
                            isActive={isActiveLink(route('admin.exams.index'), currentHref)}
                            variant="mobile"
                        >
                            Gérer Examens
                        </NavLink> */}
                        <NavLink
                            href={route('admin.users.index')}
                            isActive={isActiveLink(route('admin.users.index'), currentHref)}
                            variant="mobile"
                        >
                            Utilisateurs
                        </NavLink>
                        <NavLink
                            href={route('admin.groups.index')}
                            isActive={isActiveLink(route('admin.groups.index'), currentHref)}
                            variant="mobile"
                        >
                            Groupes
                        </NavLink>
                    </>
                )}
            </div>
        </div>
    );
};