import { route } from 'ziggy-js';
import { NavLink } from './NavLink';

interface DesktopNavigationProps {
    isAdmin: boolean;
    isTeacher: boolean;
    isStudent: boolean;
    currentHref: string;
}

const isActiveLink = (href: string, currentHref: string) => {

    return currentHref === href || currentHref.startsWith(href + '/');
};

export const DesktopNavigation = ({ isAdmin, isTeacher, isStudent, currentHref }: DesktopNavigationProps) => {
    return (
        <div className="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex sm:items-center">
            <NavLink
                href={route('dashboard')}
                icon="dashboard"
                isActive={isActiveLink(route('dashboard'), currentHref)}
                variant="desktop"
            >
                Tableau de bord
            </NavLink>

            {isStudent && (
                <>
                    <NavLink
                        href={route('student.exams.index')}
                        icon="exams"
                        isActive={isActiveLink(route('student.exams.index'), currentHref)}
                        variant="desktop"
                    >
                        Mes Examens
                    </NavLink>
                </>
            )}

            {isTeacher && (
                <>
                    <NavLink
                        href={route('teacher.exams.index')}
                        icon="exams"
                        isActive={isActiveLink(route('teacher.exams.index'), currentHref)}
                        variant="desktop"
                    >
                        Mes Examens
                    </NavLink>
                    {/* <NavLink
                        href={route('teacher.results.index')}
                        icon="results"
                        isActive={isActiveLink(route('teacher.results.index'), currentHref)}
                        variant="desktop"
                    >
                        Résultats
                    </NavLink> */}
                </>
            )}

            {isAdmin && (
                <>
                    {/* <NavLink
                        href={route('admin.exams.index')}
                        icon="manage-exams"
                        isActive={isActiveLink(route('admin.exams.index'), currentHref)}
                        variant="desktop"
                    >
                        Gérer Examens
                    </NavLink> */}
                    <NavLink
                        href={route('admin.users.index')}
                        icon="users"
                        isActive={isActiveLink(route('admin.users.index'), currentHref)}
                        variant="desktop"
                    >
                        Utilisateurs
                    </NavLink>
                    <NavLink
                        href={route('admin.groups.index')}
                        icon="groups"
                        isActive={isActiveLink(route('admin.groups.index'), currentHref)}
                        variant="desktop"
                    >
                        Groupes
                    </NavLink>
                </>
            )}
        </div>
    );
};