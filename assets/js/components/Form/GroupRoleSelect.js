import React, { Component } from 'react';
import { components } from 'react-select';
import Select from 'react-select';
import Noty from 'noty';
import API from '../../api';

const axios = require('axios').default;

const ValueContainer = ({ children, ...props }) => (
  <components.ValueContainer {...props}>{children}</components.ValueContainer>
);

const options = [{
    value: 'admin',
    label: 'Administrator'
}, {
    value: 'user',
    label: 'User'
}];

export default class GroupRoleSelect extends Component {
    constructor(props) {
        super(props);

        this.state = {
            selectedOption: options.find(el => {
                return el.value === props[0].role;
            }),
            isLoading: false
        }

        //console.log(props);
    }

    handleChange = selectedOption => {
        this.setState({
            isLoading: true
        });

        API.getInstance().put(`/api/groups/${this.props[0].group}/user/${this.props[0].user}/role`, {role: selectedOption.value})
        .then(response => {
            this.setState({ selectedOption });
            new Noty({
                text: "User's role has been changed.",
                type: "success",
                timeout: 2000
            }).show();
        })
        .catch(error => {
            console.log(error);
            new Noty({
                text: "There was an error changing user's role. Please try again later.",
                type: "error",
                timeout: 2000
            }).show();
        })
        .finally(() => this.setState({isLoading: false}));
    };

    /**
     * @param {string} group Group's slug 
     * @param {number} user User ID
     * @param {string} role Role descriptor
     */
    changeRoleRequest(group, user, role) {
        return axios.put(`/api/groups/${group}/user/${user}/role`, {role});
    }

    render() {
        const { selectedOption } = this.state;

        return (
            <Select
                isDisabled={this.state.isLoading}
                isLoading={this.state.isLoading}
                options={options}
                value={selectedOption}
                onChange={this.handleChange}
                className='react-select-container'
                classNamePrefix="react-select"
                placeholder="Search for a user"
                name="role"
            />
        );
    }
}